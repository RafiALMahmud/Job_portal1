<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\EmployerController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\MessageController;
use App\Models\Applicant;

// Home Route
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/jobs', [JobsController::class, 'index'])->name('jobs');
Route::get('/jobs/search', [JobsController::class, 'search'])->name('jobs.search');
Route::get('/jobs/{job}', [JobsController::class, 'detail'])->name('jobs.detail');

Route::group(['middleware' => 'guest'], function () {
    Route::get('/register/verify-email', [AccountController::class, 'showRegisterVerifyForm'])->name('account.showRegisterVerifyForm');
    Route::post('/register/verify-email', [AccountController::class, 'verifyRegisterOtp'])->name('account.verifyRegisterOtp');
    Route::post('/register/resend-code', [AccountController::class, 'resendRegisterOtp'])->name('account.resendRegisterOtp');

    Route::get('/login/verify-otp', [AccountController::class, 'showLoginOtp'])->name('account.showLoginOtp');
    Route::post('/login/verify-otp', [AccountController::class, 'verifyLoginOtp'])->name('account.verifyLoginOtp');
    Route::post('/login/resend-otp', [AccountController::class, 'resendLoginOtp'])->name('account.resendLoginOtp');

    Route::get('/forgot-password', [AccountController::class, 'showForgetPassword'])->name('account.forgetPassword');
    Route::post('/forgot-password/send-code', [AccountController::class, 'sendForgotPasswordCode'])->name('account.sendForgotPasswordCode');
    Route::get('/forgot-password/verify-code', [AccountController::class, 'showForgotPasswordCodeForm'])->name('account.showForgotPasswordCodeForm');
    Route::post('/forgot-password/verify-code', [AccountController::class, 'verifyForgotPasswordCode'])->name('account.verifyForgotPasswordCode');
    Route::post('/forgot-password/resend-code', [AccountController::class, 'resendForgotPasswordCode'])->name('account.resendForgotPasswordCode');
    Route::get('/reset-password', [AccountController::class, 'showResetPasswordForm'])->name('account.showResetPasswordForm');
    Route::post('/reset-password', [AccountController::class, 'resetPassword'])->name('account.resetPassword');
});


// Admin Routes
Route::group(['prefix' => 'admin', 'middleware' => ['secureSession', 'isAdmin']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/manage_users', [AdminController::class, 'index'])->name('admin.manage_users');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::put('/update-profile', [AccountController::class, 'updateProfile'])->name('admin.updateProfile');
    Route::put('/update-password', [AccountController::class, 'updatePassword'])->name('admin.updatePassword');
    
    // New routes for user management
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
    Route::post('/users/store', [AdminController::class, 'storeUser'])->name('admin.users.store');

    // Manage Jobs Routes
    Route::get('/manage-jobs', [AdminController::class, 'manageJobs'])->name('admin.manage_jobs');
    Route::delete('/jobs/{job}', [AdminController::class, 'deleteJob'])->name('admin.delete_job');

    // Manage Categories Routes
    Route::get('/manage-categories', [AdminController::class, 'manageCategories'])->name('admin.manage_categories');
    Route::post('/categories/store', [AdminController::class, 'storeCategory'])->name('admin.store_category');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('admin.delete_category');

    // New route for viewing all job applications submitted by users
    Route::get('/job-applications', [AdminController::class, 'jobApplications'])->name('admin.job_applications');
});

// Account Routes
Route::group(['prefix' => 'account'], function () {

    // Guest Routes
    Route::group(['middleware' => 'guest'], function () {
        Route::get('/register', [AccountController::class, 'registration'])->name('account.registration');
        Route::post('/process-register', [AccountController::class, 'processRegistration'])->name('account.processRegistration');
        Route::get('/login', [AccountController::class, 'login'])->name('account.login');
        Route::post('/authenticate', [AccountController::class, 'authenticate'])->name('account.authenticate');
        // Forget Password Routes
        Route::get('/forget-password', [AccountController::class, 'showForgetPassword'])->name('account.legacyForgetPassword');
    });

    // Authenticated Routes
    Route::group(['middleware' => 'secureSession'], function () {
        Route::get('/profile', [AccountController::class, 'profile'])->name('account.profile');
        Route::post('/logout', [AccountController::class, 'logout'])->name('account.logout');
        Route::post('/logout-all-devices', [AccountController::class, 'logoutAllDevices'])->name('account.logoutAllDevices');
        Route::put('/update-profile', [AccountController::class, 'updateProfile'])->name('account.updateProfile');
        Route::post('/update-profile-picture', [AccountController::class, 'updateProfilePicture'])->name('account.updateProfilePicture');
        Route::put('/update-password', [AccountController::class, 'updatePassword'])->name('account.updatePassword');
        Route::delete('/delete-account', [AccountController::class, 'deleteAccount'])->name('account.deleteAccount');
        // Notification routes for aspirant
        Route::post('/notifications/{notification}/mark-as-read', [AccountController::class, 'markNotificationAsRead'])->name('account.notifications.markAsRead');
        Route::post('/notifications/mark-all-as-read', [AccountController::class, 'markAllNotificationsAsRead'])->name('account.notifications.markAllAsRead');
        
        // Job-related routes with jobCreation middleware
        Route::group(['middleware' => 'jobCreation'], function () {
            Route::get('/create-job', [AccountController::class, 'createJob'])->name('account.createJob');
            Route::post('/save-job', [AccountController::class, 'saveJob'])->name('account.saveJob');
            Route::get('/my-jobs', [AccountController::class, 'myJobs'])->name('account.myJobs');
            Route::get('/my-jobs/edit/{jobId}', [AccountController::class, 'editJob'])->name('account.editJob');
            Route::put('/update-job/{jobId}', [AccountController::class, 'updateJob'])->name('account.updateJob');
            Route::post('/delete-job/{jobId}', [AccountController::class, 'deleteJob'])->name('account.deleteJob');
        });
    });

});

// Employer Dashboard Routes
Route::middleware(['secureSession', 'employer'])->prefix('employer')->name('employer.')->group(function () {
    Route::get('/dashboard', [EmployerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/update-company-info', [EmployerController::class, 'updateCompanyInfo'])->name('updateCompanyInfo');
    
    // Job Management Routes
    Route::get('/jobs/create', [EmployerDashboardController::class, 'createJob'])->name('jobs.create');
    Route::post('/jobs', [EmployerDashboardController::class, 'storeJob'])->name('jobs.store');
    Route::get('/jobs/{job}/edit', [EmployerDashboardController::class, 'editJob'])->name('jobs.edit');
    Route::put('/jobs/{job}', [EmployerDashboardController::class, 'updateJob'])->name('jobs.update');
    Route::delete('/jobs/{job}', [EmployerDashboardController::class, 'deleteJob'])->name('jobs.delete');
    Route::get('/jobs/{job}/applicants', [EmployerDashboardController::class, 'viewApplicants'])->name('jobs.applicants');

    // Notification Routes
    Route::post('/notifications/{notification}/mark-as-read', [EmployerDashboardController::class, 'markNotificationAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [EmployerDashboardController::class, 'markAllNotificationsAsRead'])->name('notifications.markAllAsRead');

    // Applicant Management Routes
    Route::post('/applicants/{applicant:applicant_id}/accept', [EmployerDashboardController::class, 'acceptApplicant'])->name('applicants.accept');
    Route::post('/applicants/{applicant:applicant_id}/reject', [EmployerDashboardController::class, 'rejectApplicant'])->name('applicants.reject');
    Route::post('/applicants/{applicant:applicant_id}/interview', [EmployerDashboardController::class, 'interviewApplicant'])->name('applicants.interview');
});

Route::middleware(['secureSession'])->group(function () {
    Route::get('/jobs/{job}/apply', [JobApplicationController::class, 'showApplicationForm'])->name('jobs.apply.form');
    Route::post('/jobs/{job}/apply', [JobApplicationController::class, 'apply'])->name('jobs.apply');
    Route::get('/account/jobs-applied', [JobApplicationController::class, 'jobsApplied'])->name('account.jobsApplied');
    // Saved jobs routes for aspirants
    Route::post('/jobs/{job}/save', [JobApplicationController::class, 'saveJob'])->name('jobs.save');
    Route::delete('/jobs/{job}/unsave', [JobApplicationController::class, 'unsaveJob'])->name('jobs.unsave');
    Route::get('/account/saved-jobs', [JobApplicationController::class, 'savedJobs'])->name('account.savedJobs');

    Route::get('/people', [ConnectionController::class, 'index'])->name('people.index');
    Route::post('/connections/request/{user}', [ConnectionController::class, 'sendRequest'])->name('connections.request');
    Route::post('/connections/accept/{request}', [ConnectionController::class, 'acceptRequest'])->name('connections.accept');
    Route::post('/connections/reject/{request}', [ConnectionController::class, 'rejectRequest'])->name('connections.reject');
    Route::post('/connections/cancel/{request}', [ConnectionController::class, 'cancelRequest'])->name('connections.cancel');
    Route::get('/connections', [ConnectionController::class, 'myConnections'])->name('connections.index');
    Route::get('/connections/requests', [ConnectionController::class, 'pendingRequests'])->name('connections.pending');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
});
