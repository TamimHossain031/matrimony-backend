<?php

use App\Http\Controllers\Api\V1\Admin\AdminAuditLogApiController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardApiController;
use App\Http\Controllers\Api\V1\Admin\AdminGreetingCardApiController;
use App\Http\Controllers\Api\V1\Admin\AdminPhotoApiController;
use App\Http\Controllers\Api\V1\Admin\AdminReportApiController;
use App\Http\Controllers\Api\V1\Admin\AdminUserApiController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\ContactBlockController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DiscoveryController;
use App\Http\Controllers\Api\V1\GreetingCardController;
use App\Http\Controllers\Api\V1\GreetingTemplateController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PhotoController;
use App\Http\Controllers\Api\V1\PhotoRequestController;
use App\Http\Controllers\Api\V1\PreferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ShortlistController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 (blueprint §20)
|--------------------------------------------------------------------------
| REST, versioned, clear resource boundaries. Public routes first, then the
| authenticated surface guarded by Sanctum + the account-active middleware.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ---- Public ----------------------------------------------------------
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')->name('auth.login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')->name('auth.forgot-password');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('auth.reset-password');

    // Lookup data for forms and filters.
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::get('divisions', [LookupController::class, 'divisions'])->name('divisions');
        Route::get('districts', [LookupController::class, 'districts'])->name('districts');
        Route::get('upazilas', [LookupController::class, 'upazilas'])->name('upazilas');
        Route::get('religions', [LookupController::class, 'religions'])->name('religions');
        Route::get('professions', [LookupController::class, 'professions'])->name('professions');
        Route::get('education-levels', [LookupController::class, 'educationLevels'])->name('education-levels');
    });

    // Signed, expiring photo delivery (§12). No auth — the signature is the auth.
    Route::get('photos/{photo}', [PhotoController::class, 'show'])
        ->middleware('signed')->name('photos.show');
    Route::get('photos/{photo}/blurred', [PhotoController::class, 'showBlurred'])
        ->middleware('signed')->name('photos.blurred');

    // Greeting Cards & Templates (Public / Link-based sharing, no auth required)
    Route::get('greeting-templates', [GreetingTemplateController::class, 'index'])->name('greeting-templates.index');
    Route::get('greeting-templates/{slug}', [GreetingTemplateController::class, 'show'])->name('greeting-templates.show');
    Route::post('greeting-cards', [GreetingCardController::class, 'store'])
        ->middleware('throttle:30,1')->name('greeting-cards.store');
    Route::get('public/greeting-cards/{public_id}', [GreetingCardController::class, 'showPublic'])
        ->name('public.greeting-cards.show');
    Route::post('public/greeting-cards/{public_id}/events', [GreetingCardController::class, 'recordEvent'])
        ->middleware('throttle:60,1')->name('public.greeting-cards.events');
    Route::post('public/greeting-cards/{public_id}/report', [GreetingCardController::class, 'report'])
        ->middleware('throttle:10,1')->name('public.greeting-cards.report');
    Route::patch('greeting-cards/{public_id}', [GreetingCardController::class, 'update'])
        ->middleware('throttle:30,1')->name('greeting-cards.update');
    Route::delete('greeting-cards/{public_id}', [GreetingCardController::class, 'destroy'])
        ->middleware('throttle:30,1')->name('greeting-cards.destroy');

    // ---- Authenticated ---------------------------------------------------
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Own profile
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::post('profile', [ProfileController::class, 'store'])->name('profile.store');
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('profile/status', [ProfileController::class, 'updateStatus'])->name('profile.status');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Partner preference + deal-breaker weights (D3)
        Route::get('preferences', [PreferenceController::class, 'show'])->name('preferences.show');
        Route::match(['put', 'patch'], 'preferences', [PreferenceController::class, 'update'])->name('preferences.update');

        // Discovery
        Route::get('profiles', [DiscoveryController::class, 'index'])->name('profiles.index');
        Route::get('profiles/{publicId}', [DiscoveryController::class, 'show'])->name('profiles.show');

        // Interests
        Route::post('interests', [InterestController::class, 'store'])
            ->middleware('throttle:60,1')->name('interests.store');
        Route::get('interests/received', [InterestController::class, 'received'])->name('interests.received');
        Route::get('interests/sent', [InterestController::class, 'sent'])->name('interests.sent');
        Route::patch('interests/{interest}', [InterestController::class, 'update'])->name('interests.update');

        // Shortlist
        Route::get('shortlists', [ShortlistController::class, 'index'])->name('shortlists.index');
        Route::post('shortlists', [ShortlistController::class, 'store'])->name('shortlists.store');
        Route::delete('shortlists/{publicId}', [ShortlistController::class, 'destroy'])->name('shortlists.destroy');

        // Photos
        Route::get('photos', [PhotoController::class, 'index'])->name('photos.index');
        Route::post('photos', [PhotoController::class, 'store'])->name('photos.store');
        Route::patch('photos/{photo}', [PhotoController::class, 'update'])->name('photos.update');
        Route::patch('photos/{photo}/primary', [PhotoController::class, 'setPrimary'])->name('photos.primary');
        Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');

        // Photo requests
        Route::post('photo-requests', [PhotoRequestController::class, 'store'])->name('photo-requests.store');
        Route::patch('photo-requests/{photoRequest}', [PhotoRequestController::class, 'update'])->name('photo-requests.update');

        // Relative blocklist (D2)
        Route::get('contact-blocks', [ContactBlockController::class, 'index'])->name('contact-blocks.index');
        Route::post('contact-blocks', [ContactBlockController::class, 'store'])->name('contact-blocks.store');
        Route::delete('contact-blocks/{contactBlock}', [ContactBlockController::class, 'destroy'])->name('contact-blocks.destroy');

        // Messaging
        Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages'])->name('conversations.messages');
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'send'])
            ->middleware('throttle:120,1')->name('conversations.send');

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        // Safety
        Route::get('blocks', [BlockController::class, 'index'])->name('blocks.index');
        Route::post('blocks', [BlockController::class, 'store'])->name('blocks.store');
        Route::delete('blocks/{publicId}', [BlockController::class, 'destroy'])->name('blocks.destroy');
        Route::post('reports', [ReportController::class, 'store'])->name('reports.store');

        // Verification requests (blueprint §15)
        Route::get('verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::post('verifications', [VerificationController::class, 'store'])->name('verifications.store');

        // Staff / Admin API routes (§16, §18)
        Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
            Route::get('dashboard', [AdminDashboardApiController::class, 'index'])->name('dashboard');

            Route::get('users', [AdminUserApiController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [AdminUserApiController::class, 'show'])->name('users.show');
            Route::patch('users/{user}/status', [AdminUserApiController::class, 'updateStatus'])->name('users.status');
            Route::patch('users/{user}/role', [AdminUserApiController::class, 'updateRole'])->name('users.role');
            Route::patch('users/{user}/verification-level', [AdminUserApiController::class, 'updateVerificationLevel'])->name('users.verification-level');
            Route::delete('users/{user}', [AdminUserApiController::class, 'destroy'])->name('users.destroy');

            Route::get('reports', [AdminReportApiController::class, 'index'])->name('reports.index');
            Route::get('reports/{report}', [AdminReportApiController::class, 'show'])->name('reports.show');
            Route::patch('reports/{report}', [AdminReportApiController::class, 'updateStatus'])->name('reports.update');

            Route::get('verifications', [AdminVerificationApiController::class, 'index'])->name('verifications.index');
            Route::get('verifications/{verification}', [AdminVerificationApiController::class, 'show'])->name('verifications.show');
            Route::patch('verifications/{verification}', [AdminVerificationApiController::class, 'updateStatus'])->name('verifications.update');

            Route::get('photos', [AdminPhotoApiController::class, 'index'])->name('photos.index');
            Route::patch('photos/{photo}/moderation', [AdminPhotoApiController::class, 'updateModeration'])->name('photos.moderation');
            Route::post('photos/batch', [AdminPhotoApiController::class, 'batchAction'])->name('photos.batch');
            Route::delete('photos/{photo}', [AdminPhotoApiController::class, 'destroy'])->name('photos.destroy');

            // Greeting cards moderation
            Route::get('greeting-cards', [AdminGreetingCardApiController::class, 'index'])->name('greeting-cards.index');
            Route::patch('greeting-cards/{card}/status', [AdminGreetingCardApiController::class, 'updateStatus'])->name('greeting-cards.status');
            Route::get('greeting-reports', [AdminGreetingCardApiController::class, 'reports'])->name('greeting-reports.index');
            Route::patch('greeting-reports/{report}', [AdminGreetingCardApiController::class, 'updateReportStatus'])->name('greeting-reports.update');

            Route::get('audit-logs', [AdminAuditLogApiController::class, 'index'])->name('audit-logs.index');
        });
    });
});
