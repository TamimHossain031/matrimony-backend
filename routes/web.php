<?php

use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPhotoModerationController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin authentication routes
Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

// Admin panel protected routes
Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
    Route::post('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::post('users/{user}/verification-level', [AdminUserController::class, 'updateVerificationLevel'])->name('users.verification-level');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Profiles
    Route::get('profiles', [AdminProfileController::class, 'index'])->name('profiles.index');
    Route::get('profiles/{profile}', [AdminProfileController::class, 'show'])->name('profiles.show');
    Route::post('profiles/{profile}/toggle-status', [AdminProfileController::class, 'toggleStatus'])->name('profiles.toggle-status');

    // Reports & Moderation
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::post('reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
    Route::post('reports/{report}/action-suspend', [AdminReportController::class, 'suspendUserAndResolve'])->name('reports.action-suspend');

    // Verifications
    Route::get('verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::get('verifications/{verification}', [AdminVerificationController::class, 'show'])->name('verifications.show');
    Route::post('verifications/{verification}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
    Route::post('verifications/{verification}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');
    Route::get('verifications/{verification}/document', [AdminVerificationController::class, 'document'])->name('verifications.document');

    // Photos Moderation
    Route::get('photos', [AdminPhotoModerationController::class, 'index'])->name('photos.index');
    Route::post('photos/{photo}/approve', [AdminPhotoModerationController::class, 'approve'])->name('photos.approve');
    Route::post('photos/{photo}/reject', [AdminPhotoModerationController::class, 'reject'])->name('photos.reject');
    Route::delete('photos/{photo}', [AdminPhotoModerationController::class, 'destroy'])->name('photos.destroy');
    Route::post('photos/batch', [AdminPhotoModerationController::class, 'batchAction'])->name('photos.batch');
    Route::get('photos/{photo}/preview', [AdminPhotoModerationController::class, 'preview'])->name('photos.preview');
    Route::get('photos/{photo}/preview-blurred', [AdminPhotoModerationController::class, 'previewBlurred'])->name('photos.preview-blurred');

    // Audit logs
    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});
