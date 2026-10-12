<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\InterestStatus;
use App\Enums\PhotoModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\User;
use App\Models\Verification;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', AccountStatus::Active)->count(),
            'new_users_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'verified_users' => Profile::where('verification_level', '>', 0)->count(),
            'total_profiles' => Profile::count(),
            'pending_reports' => Report::where('status', 'open')->count(),
            'suspended_accounts' => User::where('status', AccountStatus::Suspended)->count(),
            'interests_sent' => Interest::count(),
            'successful_connections' => Interest::where('status', InterestStatus::Accepted)->count(),
            'pending_verifications' => Verification::where('status', 'pending')->count(),
            'pending_photos' => ProfilePhoto::where('moderation_status', PhotoModerationStatus::Pending)->count(),
        ];

        // Database-agnostic average resolution time calculation
        $resolvedReports = Report::whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);
        $stats['avg_resolution_hours'] = $resolvedReports->isNotEmpty()
            ? round($resolvedReports->avg(fn ($r) => $r->created_at->diffInHours($r->resolved_at)), 1)
            : 0;

        $recentReports = Report::with(['reporter', 'reportedUser.profile'])
            ->where('status', 'open')
            ->latest()
            ->take(5)
            ->get();

        $recentVerifications = Verification::with(['user.profile'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentPhotos = ProfilePhoto::with(['profile.user'])
            ->where('moderation_status', PhotoModerationStatus::Pending)
            ->latest()
            ->take(8)
            ->get();

        $recentUsers = User::with('profile')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentReports',
            'recentVerifications',
            'recentPhotos',
            'recentUsers'
        ));
    }
}
