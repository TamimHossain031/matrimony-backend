<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileCardResource;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->shortlists()
            ->with(['profile.district', 'profile.profession', 'profile.educationLevel', 'profile.photos'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $items->map(fn ($s) => [
                'note' => $s->note,
                'profile' => new ProfileCardResource($s->profile),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'string', 'exists:profiles,public_id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = Profile::where('public_id', $validated['profile_id'])->firstOrFail();

        $request->user()->shortlists()->updateOrCreate(
            ['profile_id' => $profile->id],
            ['note' => $validated['note'] ?? null],
        );

        return response()->json(['message' => 'Added to shortlist.'], 201);
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $profile = Profile::where('public_id', $publicId)->firstOrFail();
        $request->user()->shortlists()->where('profile_id', $profile->id)->delete();

        return response()->json(['message' => 'Removed from shortlist.']);
    }
}
