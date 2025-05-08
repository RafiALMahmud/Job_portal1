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

        // Get top 5 highest paying jobs
        $featuredJobs = Job::with('jobType')
                   ->orderByDesc('salary')
                   ->take(5)
                   ->get();
        
        $latestJobs = Job::where('status', 1)
                   ->with('jobType')
                   ->orderBy('created_at','DESC')
                   ->take(6)
                   ->get();

        return view('front.home',[
           'categories' => $categories,
           'featuredJobs' => $featuredJobs,
           'latestJobs' => $latestJobs
        ]);
    }
}
