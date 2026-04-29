<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Category;
use App\Models\JobType;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.manage_users', compact('users'));
    }

    public function createUser()
    {
        return view('admin.create_user');
    }

    public function storeUser(Request $request)
    {
        $request->merge([
            'email_lookup_hash' => User::emailLookupHash((string) $request->email),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:5',
            'user_type' => 'required|in:aspirant,employer,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        if (User::where('email_lookup_hash', $request->email_lookup_hash)->exists()) {
            return response()->json([
                'status' => false,
                'errors' => ['email' => ['The email has already been taken.']]
            ]);
        }

        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->email_lookup_hash = $request->email_lookup_hash;
            $user->password = Hash::make($request->password);
            $user->user_type = $request->user_type;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User created successfully.',
                'redirect' => route('admin.manage_users')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => ['general' => ['An error occurred while creating the user: ' . $e->getMessage()]]
            ]);
        }
    }

    public function destroyUser(User $user)
    {
        $user->delete();
        return redirect()->route('admin.manage_users')->with('success', 'User deleted successfully.');
    }

    public function manageJobs()
    {
        $jobs = Job::with(['user', 'jobType', 'category'])
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('admin.manage_jobs', [
            'jobs' => $jobs
        ]);
    }

    public function deleteJob(Job $job)
    {
        try {
            $job->delete();
            return response()->json([
                'status' => true,
                'message' => 'Job deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting job: ' . $e->getMessage()
            ], 500);
        }
    }

    public function manageCategories()
    {
        $categories = Category::orderBy('name', 'ASC')->paginate(10);
        return view('admin.manage_categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:50|unique:categories,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        try {
            Category::create([
                'name' => $request->name,
                'status' => 1
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteCategory(Category $category)
    {
        try {
            // Check if category is being used by any jobs
            if ($category->jobs()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete category as it is being used by jobs'
                ], 422);
            }

            $category->delete();
            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function jobApplications()
    {
        $applications = Applicant::with(['user', 'job'])->orderByDesc('applied_date')->paginate(15);
        return view('admin.job_applications', compact('applications'));
    }
}
