<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileCardResource;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Services\DiscoveryService;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function __construct(
        private readonly DiscoveryService $discovery,
        private readonly MatchingService $matching,
    ) {}

    /** Discover profiles — cursor paginated, with exclusions and match scores. */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'gender' => ['sometimes', 'in:male,female'],
            'age_min' => ['sometimes', 'integer', 'between:18,100'],
            'age_max' => ['sometimes', 'integer', 'between:18,100'],
            'district_id' => ['sometimes', 'integer'],
            'religion_id' => ['sometimes', 'integer'],
            'profession_id' => ['sometimes', 'integer'],
            'education_level_id' => ['sometimes', 'integer'],
            'marital_status' => ['sometimes', 'string'],
            'height_min' => ['sometimes', 'integer'],
            'height_max' => ['sometimes', 'integer'],
            'income_min' => ['sometimes', 'integer'],
            'min_verification_level' => ['sometimes', 'integer', 'between:0,4'],
            'has_photo' => ['sometimes', 'boolean'],
            'active_within_days' => ['sometimes', 'integer'],
            'sort' => ['sometimes', 'in:recent,newest,verification,relevant'],
        ]);

        $user = $request->user();

        $page = $this->discovery->query($user, $filters)
            ->with(['district', 'profession', 'educationLevel', 'photos'])
            ->cursorPaginate(20);

        // Attach the transparent match breakdown (D3) when the viewer has a
        // partner preference set.
        $preference = $user->profile?->partnerPreference()->with('weights')->first();
        if ($preference) {
            $page->getCollection()->transform(function (Profile $profile) use ($preference) {
                $profile->match = $this->matching->evaluate($preference, $profile);

                return $profile;
            });
        }

        return response()->json([
            'data' => ProfileCardResource::collection($page->items()),
            'next_cursor' => $page->nextCursor()?->encode(),
            'has_more' => $page->hasMorePages(),
        ]);
    }

    /** View a single profile by its public, non-sequential ID. */
    public function show(Request $request, string $publicId): JsonResponse
    {
        $profile = Profile::where('public_id', $publicId)
            ->discoverable()
            ->with([
                'district', 'upazila', 'religion', 'profession', 'educationLevel',
                'educations', 'religionAttributes', 'managers', 'photos',
                'privacySettings', 'user',
            ])
            ->first();

        // A hidden/deleted profile must return 404 everywhere (§24, §28).
        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $viewer = $request->user();

        // Record the view (for "recent viewers") — but not self-views.
        if ($viewer && $viewer->id !== $profile->user_id) {
            ProfileView::create([
                'profile_id' => $profile->id,
                'viewer_id' => $viewer->id,
                'viewed_at' => now(),
            ]);
        }

        $payload = ['data' => new ProfileResource($profile)];

        // Include the match breakdown if the viewer has preferences set.
        $preference = $viewer?->profile?->partnerPreference()->with('weights')->first();
        if ($preference) {
            $payload['match'] = $this->matching->evaluate($preference, $profile);
        }

        return response()->json($payload);
    }
}
