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
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    // Show registration form
    public function registration()
    {
        return view('front.account.registration');
    }

    // Handle registration submission
    public function processRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
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

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->user_type = $request->user_type;
        $user->save();

        // Create employer record if user type is employer
        if ($request->user_type === 'employer') {
            $employer = new Employer();
            $employer->user_id = $user->id;
            $employer->company_name = ''; // This will be updated later in the employer dashboard
            $employer->save();
        }

        session()->flash('success', 'Registration successful! Please login to continue.');

        return response()->json([
            'status' => true,
            'message' => 'Registration successful! Please login to continue.',
            'redirect' => route('account.login')
        ]);
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

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // Redirect based on user type
            if ($user->user_type === 'admin') {
                return redirect()->route('admin.dashboard');
            } else {
                return redirect()->route('account.profile');
            }
        }

        return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect');
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

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:20',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Update user details
        $user->name = $request->name;
        $user->email = $request->email;
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
            $job->company_website = $request->website;
            
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
        return view('front.account.forget-password');
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // In a real application, you would verify the code from the database
        // For this demo, we'll just check if the code matches the one shown
        if ($request->code !== strval(session('reset_code'))) {
            return response()->json([
                'status' => false,
                'errors' => ['code' => ['Invalid verification code']]
            ]);
        }

        // Store email in session for password reset (already set in generateResetCode)
        // session(['reset_email' => $request->email]);

        return response()->json([
            'status' => true,
            'message' => 'Code verified successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:5|confirmed',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $email = session('reset_email');
        if (!$email) {
            return response()->json([
                'status' => false,
                'errors' => ['email' => ['Session expired. Please try again.']]
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'errors' => ['email' => ['User not found']]
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Clear the session
        session()->forget(['reset_code', 'reset_email']);

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    public function generateResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $code = rand(1000, 9999);
        session(['reset_code' => $code, 'reset_email' => $request->email]);

        return response()->json([
            'status' => true,
            'code' => $code
        ]);
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