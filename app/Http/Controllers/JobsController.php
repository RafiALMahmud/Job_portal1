<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\JobType;
use App\Models\Job;


use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class JobsController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();
        $query = Job::where('status', 1)->with(['jobType', 'category']);

        // Search using category
        if (!empty($request->category)) {
            $query = $query->where('category_id', $request->category);
        }

        // Search using job type
        if (!empty($request->jobType)) {
            $query = $query->whereIn('job_type_id', $request->jobType);
        }

        $jobs = $query->orderBy('created_at', 'DESC')
            ->get()
            ->filter(fn (Job $job) => $job->encryptedPayloadMacIsValid());

        if (!empty($request->keyword)) {
            $keyword = mb_strtolower($request->keyword);
            $jobs = $jobs->filter(function (Job $job) use ($keyword) {
                return str_contains(mb_strtolower((string) $job->title), $keyword)
                    || str_contains(mb_strtolower((string) $job->keywords), $keyword);
            });
        }

        if (!empty($request->location)) {
            $location = mb_strtolower($request->location);
            $jobs = $jobs->filter(fn (Job $job) => mb_strtolower((string) $job->location) === $location);
        }

        if (!empty($request->experience)) {
            $experience = mb_strtolower($request->experience);
            $jobs = $jobs->filter(fn (Job $job) => $this->matchesExperienceFilter($job, $experience));
        }

        // Handle sorting
        if (!empty($request->sort)) {
            switch($request->sort) {
                case 'latest':
                    $jobs = $jobs->sortByDesc('created_at');
                    break;
                case 'oldest':
                    $jobs = $jobs->sortBy('created_at');
                    break;
                case 'name_asc':
                    $jobs = $jobs->sortBy(fn (Job $job) => mb_strtolower((string) $job->title));
                    break;
                case 'name_desc':
                    $jobs = $jobs->sortByDesc(fn (Job $job) => mb_strtolower((string) $job->title));
                    break;
                default:
                    $jobs = $jobs->sortByDesc('created_at');
            }
        } else {
            $jobs = $jobs->sortByDesc('created_at');
        }

        $jobs = $this->paginateCollection($jobs->values(), 9, $request);

        // Pass data to the view
        return view('front.jobs', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'jobs' => $jobs,
        ]);
    }

    public function detail($id){
        $job = Job::with(['jobType', 'category', 'user'])->findOrFail($id);
        abort_unless($job->encryptedPayloadMacIsValid(), 404);

        return view('front.job-detail', [
            'job' => $job
        ]);
    }

    public function search(Request $request)
    {
        $query = Job::where('status', 1)->with(['jobType', 'category']);
        $role = $request->input('role', 'aspirant'); // Default to aspirant if not specified

        // Filter based on role
        if ($role === 'employer') {
            // For employers, show jobs they've posted
            if (auth()->check()) {
                $query->where('user_id', auth()->id());
            }
        } else {
            // For aspirants, show all active jobs
            $query->where('status', 1);
        }

        // Search by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $jobs = $query->orderBy('created_at', 'DESC')
            ->get()
            ->filter(fn (Job $job) => $job->encryptedPayloadMacIsValid());

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $jobs = $jobs->filter(fn (Job $job) => str_contains(mb_strtolower((string) $job->title), $search));
        }

        if ($request->filled('location')) {
            $location = mb_strtolower($request->location);
            $jobs = $jobs->filter(fn (Job $job) => str_contains(mb_strtolower((string) $job->location), $location));
        }

        $jobs = $this->paginateCollection($jobs->values(), 9, $request);
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();

        return view('front.jobs', [
            'jobs' => $jobs,
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'selectedRole' => $role,
        ]);
    }

    private function paginateCollection($items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function matchesExperienceFilter(Job $job, string $experience): bool
    {
        $jobExperience = mb_strtolower(trim((string) $job->experience));
        if ($jobExperience === $experience) {
            return true;
        }

        if ($experience === '10_plus') {
            return str_contains($jobExperience, '10+') || str_contains($jobExperience, '10 plus');
        }

        if (!ctype_digit($experience)) {
            return false;
        }

        preg_match_all('/\d+/', $jobExperience, $matches);
        $years = array_map('intval', $matches[0]);
        if ($years === []) {
            return false;
        }

        $target = (int) $experience;

        if (count($years) === 1) {
            return $years[0] === $target;
        }

        return $target >= min($years) && $target <= max($years);
    }
}
