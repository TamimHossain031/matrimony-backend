<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProfileView;
use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ProfileCompletionService $completion) {}

    /** User dashboard summary (blueprint §18). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        $recentViewers = $profile
            ? ProfileView::where('profile_id', $profile->id)
                ->latest('viewed_at')
                ->limit(10)
                ->pluck('viewer_id')
                ->unique()
                ->count()
            : 0;

        return response()->json([
            'data' => [
                'profile_completion' => $profile ? $this->completion->percent($profile) : 0,
                'missing_sections' => $profile ? $this->completion->missingSections($profile) : ['basics'],
                'verification_level' => $profile?->verification_level ?? 0,
                'new_interests' => $user->receivedInterests()->where('status', 'pending')->count(),
                'accepted_connections' => $user->receivedInterests()->where('status', 'accepted')->count()
                    + $user->sentInterests()->where('status', 'accepted')->count(),
                'shortlist_count' => $user->shortlists()->count(),
                'unread_notifications' => $user->notifications()->whereNull('read_at')->count(),
                'recent_viewers' => $recentViewers,
            ],
        ]);
    }
}
