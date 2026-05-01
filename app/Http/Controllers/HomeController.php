<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Job;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // this method will show our home page
    public function index()  {
        $categories = Category::where('status', 1)->orderBy('name','ASC')->take(8)->get();

        // Salary is encrypted in storage, so ranking is done after model decryption.
        $topPaidJobs = Job::with('jobType')
                   ->where('status', 1)
                   ->get()
                   ->filter(fn (Job $job) => $job->encryptedPayloadMacIsValid())
                   ->filter(fn (Job $job) => $job->salary !== null && $job->salary !== '')
                   ->sortByDesc(fn (Job $job) => (float) preg_replace('/[^0-9.]/', '', (string) $job->salary))
                   ->take(5)
                   ->values();
        
        $latestJobs = Job::where('status', 1)
                   ->with('jobType')
                   ->orderBy('created_at','DESC')
                   ->get()
                   ->filter(fn (Job $job) => $job->encryptedPayloadMacIsValid())
                   ->take(6)
                   ->values();

        return view('front.home',[
           'categories' => $categories,
           'topPaidJobs' => $topPaidJobs,
           'latestJobs' => $latestJobs
        ]);
    }
}
