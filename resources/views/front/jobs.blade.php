@extends('front.layouts.app')

@section('main')

<section class="section-3 py-5 bg-2 ">
    <div class="container">     
        <div class="row">
            <div class="col-6 col-md-10 ">
                <h2>Find Jobs</h2>  
            </div>
            <div class="col-6 col-md-2">
                <div class="align-end">
                    <select name="sort" id="sort" class="form-control">
                        <option value="latest" {{ Request::get('sort') == 'latest' ? 'selected' : '' }}>Latest Jobs</option>
                        <option value="oldest" {{ Request::get('sort') == 'oldest' ? 'selected' : '' }}>Oldest Jobs</option>
                        <option value="name_asc" {{ Request::get('sort') == 'name_asc' ? 'selected' : '' }}>Job Title (A-Z)</option>
                        <option value="name_desc" {{ Request::get('sort') == 'name_desc' ? 'selected' : '' }}>Job Title (Z-A)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row pt-5">
            <div class="col-md-4 col-lg-3 sidebar mb-4">
                <form action=""name ="searchForm" id="searchForm">
                    <div class="card border-0 shadow p-4" style="transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)';">
                        <div class="mb-4">
                            <h2>Keywords</h2>
                            <input value="{{ Request::get('keyword') }}" type="text" name="keyword" id="keyword" placeholder="Keywords" class="form-control" style="transition: all 0.3s ease;" onfocus="this.style.boxShadow='0 0 0 0.2rem rgba(0,123,255,0.25)'; this.style.borderColor='#007bff';" onblur="this.style.boxShadow='none'; this.style.borderColor='#ced4da';">
                        </div>

                        <div class="mb-4">
                            <h2>Location</h2>
                            <input value="{{ Request::get('location') }}" type="text" name="location" id="location" placeholder="Location" class="form-control" style="transition: all 0.3s ease;" onfocus="this.style.boxShadow='0 0 0 0.2rem rgba(0,123,255,0.25)'; this.style.borderColor='#007bff';" onblur="this.style.boxShadow='none'; this.style.borderColor='#ced4da';">
                        </div>

                        <div class="mb-4">
                            <h2>Category</h2>
                            <select name="category" id="category" class="form-control" style="transition: all 0.3s ease;" onfocus="this.style.boxShadow='0 0 0 0.2rem rgba(0,123,255,0.25)'; this.style.borderColor='#007bff';" onblur="this.style.boxShadow='none'; this.style.borderColor='#ced4da';">
                                <option value="">Select a Category</option>
                                @if(isset($categories) && $categories->isNotEmpty())
                                   @foreach($categories as $category)
                                    <option  {{ (Request::get('category')==$category->id)? 'selected': '' }} value="{{$category->id }}">{{$category->name }}</option>
                                   @endforeach
                                @endif
                            </select>
                        </div>                   

                        <div class="mb-4">
                            <h2>Job Type</h2>
                            @if($jobTypes->isNotEmpty())
                                @foreach($jobTypes as $jobType)
                                <div class="form-check mb-2"> 
                                    <input class="form-check-input" name="jobType[]" type="checkbox" value="{{ $jobType->id }}" id="job-type-{{ $jobType->id }}" {{ in_array($jobType->id, (array)request()->get('jobType', [])) ? 'checked' : '' }}>    
                                    <label class="form-check-label" for="job-type-{{ $jobType->id }}">{{ $jobType->name }}</label>
                                </div>
                                @endforeach
                            @endif
                        
                        </div>

                        <div class="mb-4">
                            <h2>Experience</h2>
                            <select name="experience" id="experience" class="form-control">
                                <option value="">Select Experience</option>
                                <option value="1" {{ Request::get('experience') == '1' ? 'selected' : '' }}>1 Year</option>
                                <option value="2" {{ Request::get('experience') == '2' ? 'selected' : '' }}>2 Years</option>
                                <option value="3" {{ Request::get('experience') == '3' ? 'selected' : '' }}>3 Years</option>
                                <option value="4" {{ Request::get('experience') == '4' ? 'selected' : '' }}>4 Years</option>
                                <option value="5" {{ Request::get('experience') == '5' ? 'selected' : '' }}>5 Years</option>
                                <option value="6" {{ Request::get('experience') == '6' ? 'selected' : '' }}>6 Years</option>
                                <option value="7" {{ Request::get('experience') == '7' ? 'selected' : '' }}>7 Years</option>
                                <option value="8" {{ Request::get('experience') == '8' ? 'selected' : '' }}>8 Years</option>
                                <option value="9" {{ Request::get('experience') == '9' ? 'selected' : '' }}>9 Years</option>
                                <option value="10" {{ Request::get('experience') == '10' ? 'selected' : '' }}>10 Years</option>
                                <option value="10_plus" {{ Request::get('experience') == '10_plus' ? 'selected' : '' }}>10+ Years</option>
                            </select>
                        </div>                    
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Search</button>
                            <button type="button" id="resetBtn" class="btn btn-outline-secondary btn-lg">Reset Filters</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-8 col-lg-9 ">
                <div class="job_listing_area">                    
                    <div class="job_lists">
                    <div class="row">
                        @if($jobs->isNotEmpty())
                           @foreach($jobs as $job)
                           <div class="col-md-4">
                            <div class="card border-0 p-3 shadow mb-4" style="transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)';">
                                <div class="card-body">
                                    <h3 class="border-0 fs-5 pb-2 mb-0">{{ $job->title }}</h3>
                                    <p>{{ Str::words($job->description, $words=10, '...') }}</p>
                                    <div class="bg-light p-3 border">
                                        <p class="mb-0">
                                            <span class="fw-bolder"><i class="fa fa-map-marker"></i></span>
                                            <span class="ps-1">{{ $job->location }}</span>
                                        </p>
                                        <p class="mb-0">
                                            <span class="fw-bolder"><i class="fa fa-clock-o"></i></span>
                                            <span class="ps-1">{{ $job->jobType->name }}</span>
                                        </p>
                                        {{-- <p>Keywords:{{ $job->keywords }}</p>
                                        <p>Category:{{ $job->category->name  }}</p>
                                        <p>Experience:{{ $job->experience  }}</p> --}}
                                        @if (!is_null($job->salary))
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
                                        @endif
                                    </div>

                                    <div class="d-grid mt-3">
                                        <a href="{{ route('jobs.detail', $job->id) }}" class="btn btn-primary btn-lg" style="transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 8px rgba(0,123,255,0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>        


                           @endforeach


                        @else
                        <div class="col-md-12">Jobs not found</div>


                        @endif
                                           
                    </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

@endsection

@section('customJs')
<script>
    // Reset button functionality
    $("#resetBtn").click(function() {
        // Clear all input fields
        $("#keyword").val('');
        $("#location").val('');
        $("#category").val('');
        $("#experience").val('');
        $("#sort").val('latest');
        
        // Uncheck all checkboxes
        $("input:checkbox[name='jobType[]']").prop('checked', false);
        
        // Submit the form to refresh the results
        window.location.href = '{{ route("jobs") }}';
    });

    // Handle sort change immediately
    $("#sort").change(function() {
        var selectedSort = $(this).val();
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', selectedSort);
        window.location.href = currentUrl.toString();
    });

    $("#searchForm").submit(function(e){
        e.preventDefault();

        var url = '{{ route("jobs") }}';
        var params = [];

        // Get all form values
        var keyword = $("#keyword").val();
        var location = $("#location").val();
        var category = $("#category").val();
        var experience = $("#experience").val();
        var sort = $("#sort").val();

        // Get selected job types using map
        var jobTypes = $("input:checkbox[name='jobType[]']:checked").map(function(){
            return $(this).val();
        }).get();

        // Add parameters if they have values
        if (keyword != "") {
            params.push('keyword=' + encodeURIComponent(keyword));
        }

        if (location != "") {
            params.push('location=' + encodeURIComponent(location));
        }

        if (category != "") {
            params.push('category=' + encodeURIComponent(category));
        }

        if (experience != "") {
            params.push('experience=' + encodeURIComponent(experience));
        }

        // Always include sort if it has a value
        if (sort != "") {
            params.push('sort=' + encodeURIComponent(sort));
        }

        // Add job types
        if (jobTypes.length > 0) {
            jobTypes.forEach(function(type) {
                params.push('jobType[]=' + encodeURIComponent(type));
            });
        }

        // Build final URL
        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        window.location.href = url;
    });

    // Initialize checkboxes based on URL parameters
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var jobTypeParams = urlParams.getAll('jobType[]');
        
        if (jobTypeParams.length > 0) {
            jobTypeParams.forEach(function(value) {
                $("input:checkbox[name='jobType[]'][value='" + value + "']").prop('checked', true);
            });
        }

        // Set the sort dropdown to the current value from URL
        var currentSort = urlParams.get('sort');
        if (currentSort) {
            $("#sort").val(currentSort);
        }
    });
</script>

@endsection
