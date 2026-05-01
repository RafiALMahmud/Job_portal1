@extends('front.layouts.app')

@section('main')
{{-- #hero section  --}}
<section class="section-0 lazy d-flex bg-image-style dark align-items-center " class= " "data-bg="{{ asset('assets/images/banner5.jpg') }}">
    <div class="container">
        <div class="row">
            <div class="col-12 col-xl-8">
                <h1 id="typewriter"></h1>
                <div class="banner-btn mt-5">
                    <a href="#" class="btn btn-primary mb-4 mb-sm-0" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.08) translateY(-3px) rotate(-1deg)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.18), 0 1.5px 6px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">Explore Now</a>
                </div>
            </div>
        </div>
    </div>
</section>
{{-- #searching section  --}}
<section class="section-1 py-5 "> 
    <div class="container">
        <div class="card border-0 shadow p-5">
            <form action="{{ route('jobs.search') }}" method="GET">
                <div class="row">
                    <div class="col-md-2 mb-3 mb-sm-3 mb-lg-0">
                        <select name="role" id="role" class="form-control">
                            <option value="aspirant" {{ request('role') == 'aspirant' ? 'selected' : '' }}>Aspirant</option>
                            <option value="employer" {{ request('role') == 'employer' ? 'selected' : '' }}>Employer</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-sm-3 mb-lg-0">
                        <input type="text" class="form-control" name="search" id="search" placeholder="Job Title" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3 mb-3 mb-sm-3 mb-lg-0">
                        <input type="text" class="form-control" name="location" id="location" placeholder="Location" value="{{ request('location') }}">
                    </div>
                    <div class="col-md-2 mb-3 mb-sm-3 mb-lg-0">
                        <select name="category" id="category" class="form-control">
                            <option value="">Select a Category</option>
                            @if(isset($categories) && $categories->isNotEmpty())
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-xs-3 mb-sm-3 mb-lg-0">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.08) translateY(-3px) rotate(-1deg)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.18), 0 1.5px 6px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">Search</button>
                        </div>
                    </div>
                </div>            
            </form>
        </div>
    </div>
</section>
{{-- popular categories section  --}}
<section class="section-2 bg-2 py-5">
    <div class="container">
        <h2>Popular Categories</h2>
        <div class="row pt-5">

            @if ($categories->isNotEmpty())
            @foreach ($categories as $category) 
            <div class="col-lg-4 col-xl-3 col-md-6">
                <div class="single_catagory" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1); padding: 20px; border-radius: 8px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onmouseover="this.style.transform='scale(1.04) translateY(-8px) rotate(0.5deg)';this.style.boxShadow='0 12px 36px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 5px rgba(0,0,0,0.05)';">
                    <a href="{{ route('jobs.search', ['category' => $category->id]) }}">
                        <h4 class="pb-2">{{ $category->name }}</h4>
                    </a>
                    <p class="mb-0">
                        <span>{{ $category->jobs()->where('status', 1)->count() }}</span> Available position
                    </p>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</section>
{{-- featured jobs section  --}}
<section class="section-3 py-5">
    <div class="container">
        <h2>Popular Jobs</h2>
        <div class="row pt-5">
            <div class="job_listing_area">
                <div class="job_lists">
                    <div class="row">
                        <div class="col-12">
                            <div id="featuredJobsCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    @foreach($topPaidJobs as $key => $job)
                                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                            <div class="row justify-content-center">
                                                <div class="col-md-8">
                                                    <div class="card border-0 p-3 shadow mb-4" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.04) translateY(-8px) rotate(0.5deg)';this.style.boxShadow='0 12px 36px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                                                        <div class="card-body">
                                                            <h3 class="border-0 fs-5 pb-2 mb-0">{{ $job->title }}</h3>
                                                            <p>{{ Str::words($job->description, 5) }}.</p>
                                                            <div class="bg-light p-3 border">
                                                                <p class="mb-0">
                                                                    <span class="fw-bolder"><i class="fa fa-map-marker"></i></span>
                                                                    <span class="ps-1">{{ $job->location }}</span>
                                                                </p>
                                                                <p class="mb-0">
                                                                    <span class="fw-bolder"><i class="fa fa-clock-o"></i></span>
                                                                    <span class="ps-1">{{ $job->jobType->name }}</span>
                                                                </p>
                                                                <p class="mb-0">
                                                                    <span class="fw-bolder">৳</span>
                                                                    <span class="ps-1">
                                                                        @if (is_numeric($job->salary))
                                                                            {{ number_format((float) $job->salary) }}
                                                                        @else
                                                                            {{ $job->salary }}
                                                                        @endif
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="d-grid mt-3">
                                                                <a href="{{ route('jobs.detail', $job->id) }}" class="btn btn-primary btn-lg" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.08) translateY(-3px) rotate(-1deg)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.18), 0 1.5px 6px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">Details</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#featuredJobsCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#featuredJobsCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                                <div class="carousel-indicators">
                                    @foreach($topPaidJobs as $key => $job)
                                        <button type="button" data-bs-target="#featuredJobsCarousel" data-bs-slide-to="{{ $key }}" class="{{ $key == 0 ? 'active' : '' }}" aria-current="{{ $key == 0 ? 'true' : 'false' }}" aria-label="Slide {{ $key + 1 }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- latest jobs section  --}}
<section class="section-3 bg-2 py-5">
    <div class="container">
        <h2>Latest Jobs</h2>
        <div class="row pt-5">
            <div class="job_listing_area">                    
                <div class="job_lists">
                    <div class="row">
                        @if ($latestJobs->isNotEmpty())
                            @foreach ($latestJobs as $latestjob)
                                <div class="col-md-4">
                                    <div class="card border-0 p-3 shadow mb-4" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.04) translateY(-8px) rotate(0.5deg)';this.style.boxShadow='0 12px 36px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                                        <div class="card-body">
                                            <h3 class="border-0 fs-5 pb-2 mb-0">{{ $latestjob->title }}</h3>
                                            <p>{{ Str::words($latestjob->description, 5) }}.</p>
                                            <div class="bg-light p-3 border">
                                                <p class="mb-0">
                                                    <span class="fw-bolder"><i class="fa fa-map-marker"></i></span>
                                                    <span class="ps-1">{{ $latestjob->location }}</span>
                                                </p>
                                                <p class="mb-0">
                                                    <span class="fw-bolder"><i class="fa fa-clock-o"></i></span>
                                                    <span class="ps-1">{{ $latestjob->jobType->name }}</span>
                                                </p>
                                                @if (!is_null($latestjob->salary))
                                                    <p class="mb-0">
                                                        <span class="fw-bolder">৳</span>
                                                        <span class="ps-1">
                                                            @if (is_numeric($latestjob->salary))
                                                                {{ number_format((float) $latestjob->salary) }}
                                                            @else
                                                                {{ $latestjob->salary }}
                                                            @endif
                                                        </span>
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="d-grid mt-3">
                                                <a href="{{ route('jobs.detail', $latestjob->id) }}" class="btn btn-primary btn-lg" style="transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s cubic-bezier(.4,0,.2,1);" onmouseover="this.style.transform='scale(1.08) translateY(-3px) rotate(-1deg)';this.style.boxShadow='0 8px 32px rgba(0,0,0,0.18), 0 1.5px 6px rgba(0,0,0,0.10)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>                      
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('customJs')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textArray = [
        "Find your dream job",
        "Discover new opportunities",
        "Apply to thousands of jobs"
    ];
    let textIndex = 0;
    let charIndex = 0;
    const typeSpeed = 80;
    const eraseSpeed = 40;
    const delayBetween = 1500;
    const typewriter = document.getElementById('typewriter');

    function type() {
        if (charIndex < textArray[textIndex].length) {
            typewriter.textContent += textArray[textIndex].charAt(charIndex);
            charIndex++;
            setTimeout(type, typeSpeed);
        } else {
            setTimeout(erase, delayBetween);
        }
    }

    function erase() {
        if (charIndex > 0) {
            typewriter.textContent = textArray[textIndex].substring(0, charIndex - 1);
            charIndex--;
            setTimeout(erase, eraseSpeed);
        } else {
            textIndex = (textIndex + 1) % textArray.length;
            setTimeout(type, typeSpeed);
        }
    }

    type();

    // Initialize the carousel with custom settings
    var myCarousel = new bootstrap.Carousel(document.getElementById('featuredJobsCarousel'), {
        interval: 5000, // Change slide every 5 seconds
        wrap: true,
        keyboard: true,
        pause: 'hover'
    });
});
</script>
@endsection
