<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BlockController;
use App\Http\Controllers\Api\V1\ContactBlockController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DiscoveryController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PhotoController;
use App\Http\Controllers\Api\V1\PhotoRequestController;
use App\Http\Controllers\Api\V1\PreferenceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ShortlistController;
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
    });
});
