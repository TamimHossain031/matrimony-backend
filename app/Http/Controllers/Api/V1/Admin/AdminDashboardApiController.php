<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
use Illuminate\Http\JsonResponse;

class AdminDashboardApiController extends Controller
{
    public function index(): JsonResponse
    {
        $resolvedReports = Report::whereNotNull('resolved_at')->get(['created_at', 'resolved_at']);
        $avgResolutionHours = $resolvedReports->isNotEmpty()
            ? round($resolvedReports->avg(fn ($r) => $r->created_at->diffInHours($r->resolved_at)), 1)
            : 0;

        return response()->json([
            'stats' => [
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
                'avg_report_resolution_hours' => $avgResolutionHours,
            ],
        ]);
    }
}
