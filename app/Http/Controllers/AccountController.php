<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\JobType;
use App\Models\Job;
use App\Models\Employer;
use App\Models\VerificationCode;
use App\Services\Auth\EmailOtpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function __construct(private EmailOtpService $emailOtpService)
    {
    }

    // Show registration form
    public function registration()
    {
        return view('front.account.registration');
    }

    // Handle registration submission
    public function processRegistration(Request $request)
    {
        $submittedEmail = mb_strtolower(trim((string) $request->email));

        $request->merge([
            'email' => $submittedEmail,
            'email_lookup_hash' => User::emailLookupHash($submittedEmail),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
            'user_type' => 'required|in:aspirant,employer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }

        if (User::where('email_lookup_hash', $request->email_lookup_hash)->exists()) {
            return response()->json([
                'status' => false,
                'errors' => ['email' => ['The email has already been taken.']],
            ]);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->email_lookup_hash = $request->email_lookup_hash;
        $user->password = Hash::make($request->password);
        $user->user_type = $request->user_type;
        $user->email_verified_at = null;
        $user->is_email_verified = false;
        $user->save();

        // Create employer record if user type is employer
        if ($request->user_type === 'employer') {
            $employer = new Employer();
            $employer->user_id = $user->id;
            $employer->company_name = ''; // This will be updated later in the employer dashboard
            $employer->save();
        }

        try {
            $this->emailOtpService->createAndSendOtp(
                $user,
                (string) $request->email,
                VerificationCode::PURPOSE_REGISTER,
                5
            );
        } catch (\Throwable $exception) {
            Log::error('Hirely registration OTP email failed', [
                'email' => $submittedEmail,
                'error' => $exception->getMessage(),
            ]);

            if (isset($employer)) {
                $employer->delete();
            }
            $user->delete();

            return response()->json([
                'status' => false,
                'errors' => ['email' => ['Hirely could not send the verification email. Please check SMTP settings and try again.']],
            ]);
        }

        $request->session()->put('pending_register_user_id', $user->id);
        $request->session()->put('pending_register_email', $submittedEmail);

        session()->flash('success', 'Registration successful. Enter the Hirely code sent to your email.');

        return response()->json([
            'status' => true,
            'message' => 'Registration successful. Enter the Hirely code sent to your email.',
            'redirect' => route('account.showRegisterVerifyForm')
        ]);
    }

    public function showRegisterVerifyForm()
    {
        if (!session('pending_register_user_id')) {
            return redirect()->route('account.registration')->with('error', 'Please register first.');
        }

        return view('front.account.register-verify-email');
    }

    public function verifyRegisterOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $userId = session('pending_register_user_id');
        if (!$userId) {
            return redirect()->route('account.registration')->with('error', 'Your verification session expired. Please register again.');
        }

        $user = User::findOrFail($userId);
        $result = $this->emailOtpService->verifyOtp($user, VerificationCode::PURPOSE_REGISTER, (string) $request->otp);

        if (!$result['status']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $user->email_verified_at = now();
        $user->is_email_verified = true;
        $user->save();

        $request->session()->forget(['pending_register_user_id', 'pending_register_email']);

        return redirect()->route('account.login')->with('success', 'Your Hirely account is verified. Please login.');
    }

    public function resendRegisterOtp(Request $request)
    {
        $userId = session('pending_register_user_id');
        $email = session('pending_register_email');

        if (!$userId || !$email) {
            return redirect()->route('account.registration')->with('error', 'Please register first.');
        }

        $result = $this->emailOtpService->resendOtp(
            User::findOrFail($userId),
            (string) $email,
            VerificationCode::PURPOSE_REGISTER,
            5
        );

        return back()->with($result['status'] ? 'success' : 'error', $result['message']);
    }

    // Show login form
    public function login()
    {
        return view('front.account.login');
    }

    // Handle login authentication
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $submittedEmail = mb_strtolower(trim((string) $request->email));
        $emailLookupHash = User::emailLookupHash($submittedEmail);
        $user = User::where('email_lookup_hash', $emailLookupHash)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if (!$user->is_email_verified && $user->user_type !== 'admin') {
                return redirect()->route('account.login')->with('error', 'Please verify your Hirely account email before logging in.');
            }

            $request->session()->regenerate();
            try {
                $this->emailOtpService->createAndSendOtp(
                    $user,
                    $submittedEmail,
                    VerificationCode::PURPOSE_LOGIN,
                    5
                );
            } catch (\Throwable $exception) {
                Log::error('Hirely login OTP email failed', [
                    'email' => $submittedEmail,
                    'error' => $exception->getMessage(),
                ]);

                return redirect()->route('account.login')->with('error', 'Hirely could not send the login code. Please check SMTP settings and try again.');
            }

            $request->session()->put('pending_login_user_id', $user->id);
            $request->session()->put('pending_login_email', $submittedEmail);
            
            return redirect()->route('account.showLoginOtp');
        }

        return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect');
    }

    public function showLoginOtp()
    {
        if (!session('pending_login_user_id')) {
            return redirect()->route('account.login')->with('error', 'Please enter your email and password first.');
        }

        return view('front.account.login-verify-otp');
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $userId = session('pending_login_user_id');
        if (!$userId) {
            return redirect()->route('account.login')->with('error', 'Your login session expired. Please try again.');
        }

        $user = User::findOrFail($userId);
        $result = $this->emailOtpService->verifyOtp($user, VerificationCode::PURPOSE_LOGIN, (string) $request->otp);

        if (!$result['status']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['pending_login_user_id', 'pending_login_email']);

        return redirect()->route($user->user_type === 'admin' ? 'admin.dashboard' : 'account.profile');
    }

    public function resendLoginOtp(Request $request)
    {
        $userId = session('pending_login_user_id');
        if (!$userId) {
            return redirect()->route('account.login')->with('error', 'Please enter your email and password first.');
        }

        $email = session('pending_login_email');
        if (!$email) {
            return redirect()->route('account.login')->with('error', 'Your login session expired. Please try again.');
        }

        $result = $this->emailOtpService->resendOtp(
            User::findOrFail($userId),
            (string) $email,
            VerificationCode::PURPOSE_LOGIN,
            5
        );

        return back()->with($result['status'] ? 'success' : 'error', $result['message']);
    }

    // Show user profile
    public function profile()
    {
        $user = Auth::user();
        // Fetch notifications for aspirant
        $notifications = \App\Models\Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();
        $unreadNotifications = \App\Models\Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
        return view('front.account.profile', compact('user', 'notifications', 'unreadNotifications'));
    }

    // Handle profile update
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->merge([
            'email_lookup_hash' => User::emailLookupHash((string) $request->email),
        ]);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:20',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        if (User::where('email_lookup_hash', $request->email_lookup_hash)->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'status' => false,
                'errors' => ['email' => ['The email has already been taken.']]
            ]);
        }

        // Update user details
        $user->name = $request->name;
        $user->email = $request->email;
        $user->email_lookup_hash = $request->email_lookup_hash;
        $user->mobile = $request->mobile;
        $user->designation = $request->designation;
        $user->save();

        // Flash success message
        session()->flash('success', 'Profile updated successfully.');

        // Determine the redirect route based on user type
        $redirectRoute = $user->user_type === 'admin' ? route('admin.dashboard') : route('account.profile');

        return response()->json([
            'status' => true,
            'redirect' => $redirectRoute, // Return the redirect route
            'errors' => []
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $user = Auth::user();

        // Delete old image if exists
        if ($user->image) {
            Storage::disk('public')->delete('profile/' . $user->image);
        }

        // Upload new image
        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        
        // Store the image in storage/app/public/profile
        $image->storeAs('profile', $imageName, 'public');

        // Update user profile
        $user->image = $imageName;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile picture updated successfully'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:5|confirmed', // Ensure new_password matches new_password_confirmation
            'new_password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Check if the old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => false,
                'errors' => ['old_password' => ['The old password does not match our records.']]
            ]);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Flash success message
        session()->flash('success', 'Password updated successfully.');

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully.'
        ]);
    }

    // Logout the user
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('account.login');
    }

    public function createJob()
    {
        $categories = Category::orderBy('name', 'asc')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'asc')->where('status', 1)->get();

        return view('front.account.job.create', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function saveJob(Request $request)
    {
        // Define validation rules
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required|exists:categories,id', // Ensure category exists in the database
            'jobType' => 'required|exists:job_types,id',   // Ensure job type exists in the database
            'vacancy' => 'required|integer',
            'location' => 'required|max:50',
            'description' => 'required',
            'experience' => 'required|in:1,2,3,4,5,6,7,8,9,10,10_plus',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(), $rules);

        // If validation passes
        if ($validator->passes()) {
            $job = new Job();
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = Auth::user()->id; // Assuming the user is logged in
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            $job->save();

            // Flash success message
            session()->flash('success', 'Job added successfully!');

            return response()->json([
                'status' => true,
                'errors' => [],
            ]);
        } else {
            // If validation fails, return errors
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function myJobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)->with('jobType')->orderBy('created_at', 'DESC')->paginate(10);

        return view('front.account.job.my-jobs', [
            'jobs' => $jobs
        ]);
    }

    public function editJob(Request $request, $id)
    {
        $categories = Category::orderBy('name', 'asc')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'asc')->where('status', 1)->get();

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id
        ])->first();

        if ($job === null) {
            abort(404);
        }

        return view('front.account.job.edit', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'job' => $job
        ]);
    }

    public function updateJob(Request $request, $id)
    {
        // Define validation rules
        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required|exists:categories,id',
            'jobType' => 'required|exists:job_types,id',
            'vacancy' => 'required|integer',
            'location' => 'required|max:50',
            'description' => 'required',
            'experience' => 'required|in:1,2,3,4,5,6,7,8,9,10,10_plus',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $job = Job::where([
                'user_id' => Auth::user()->id,
                'id' => $id
            ])->first();

            if (!$job) {
                return response()->json([
                    'status' => false,
                    'errors' => ['job' => ['Job not found!']]
                ]);
            }

            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            
            $job->save();

            session()->flash('success', 'Job updated successfully!');

            return response()->json([
                'status' => true
            ]);
        }

        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ]);
    }

    public function deleteJob(Request $request)
    {
        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $request->jobId
        ])->first();

        if (!$job) {
            return response()->json([
                'status' => false,
                'message' => 'Job not found!'
            ]);
        }

        $job->delete();  // Delete the job

        session()->flash('success', 'Job deleted successfully!');  // Set the success message
        return response()->json([
            'status' => true
        ]);
    }

    public function deleteAccount()
    {
        $user = Auth::user();
        
        // Delete all jobs associated with the user
        Job::where('user_id', $user->id)->delete();
        
        // Delete the user
        $user->delete();
        
        // Logout the user
        Auth::logout();
        
        return response()->json([
            'status' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    public function showForgetPassword()
    {
        return view('front.account.forgot-password');
    }

    public function sendForgotPasswordCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = mb_strtolower(trim((string) $request->email));
        $user = User::where('email_lookup_hash', User::emailLookupHash($email))->first();

        if ($user) {
            try {
                $this->emailOtpService->createAndSendOtp(
                    $user,
                    $email,
                    VerificationCode::PURPOSE_FORGOT_PASSWORD,
                    10
                );
            } catch (\Throwable $exception) {
                return back()->with('error', 'Hirely could not send the password reset code. Please check SMTP settings and try again.');
            }
        }

        $request->session()->put('forgot_password_email_lookup_hash', User::emailLookupHash($email));
        $request->session()->put('forgot_password_email', $email);

        return redirect()->route('account.showForgotPasswordCodeForm')
            ->with('success', 'If the email exists, a verification code has been sent.');
    }

    public function showForgotPasswordCodeForm()
    {
        if (!session('forgot_password_email_lookup_hash')) {
            return redirect()->route('account.forgetPassword')->with('error', 'Please enter your email first.');
        }

        return view('front.account.forgot-password-verify-code');
    }

    public function verifyForgotPasswordCode(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $emailLookupHash = session('forgot_password_email_lookup_hash');
        if (!$emailLookupHash) {
            return redirect()->route('account.forgetPassword')->with('error', 'Your reset session expired. Please try again.');
        }

        $user = User::where('email_lookup_hash', $emailLookupHash)->first();
        if (!$user) {
            return back()->withErrors(['otp' => 'Invalid or expired verification code.']);
        }

        $result = $this->emailOtpService->verifyOtp($user, VerificationCode::PURPOSE_FORGOT_PASSWORD, (string) $request->otp);
        if (!$result['status']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $request->session()->put('password_reset_verified_user_id', $user->id);

        return redirect()->route('account.showResetPasswordForm')->with('success', 'Code verified. Please enter a new password.');
    }

    public function resendForgotPasswordCode(Request $request)
    {
        $emailLookupHash = session('forgot_password_email_lookup_hash');
        $email = session('forgot_password_email');

        if (!$emailLookupHash || !$email) {
            return redirect()->route('account.forgetPassword')->with('error', 'Please enter your email first.');
        }

        $user = User::where('email_lookup_hash', $emailLookupHash)->first();
        if (!$user) {
            return back()->with('success', 'If the email exists, a verification code has been sent.');
        }

        $result = $this->emailOtpService->resendOtp(
            $user,
            (string) $email,
            VerificationCode::PURPOSE_FORGOT_PASSWORD,
            10
        );

        return back()->with($result['status'] ? 'success' : 'error', $result['message']);
    }

    public function showResetPasswordForm()
    {
        if (!session('password_reset_verified_user_id')) {
            return redirect()->route('account.forgetPassword')->with('error', 'Please verify your Hirely password reset code first.');
        }

        return view('front.account.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:5|confirmed',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $userId = session('password_reset_verified_user_id');
        if (!$userId) {
            return redirect()->route('account.forgetPassword')->with('error', 'Please verify your Hirely password reset code first.');
        }

        $user = User::findOrFail($userId);
        $user->password = Hash::make($request->password);
        $user->save();

        $request->session()->forget([
            'forgot_password_email_lookup_hash',
            'forgot_password_email',
            'password_reset_verified_user_id',
        ]);

        return redirect()->route('account.login')->with('success', 'Your Hirely password has been reset. Please login.');
    }

    public function markNotificationAsRead(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsAsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
