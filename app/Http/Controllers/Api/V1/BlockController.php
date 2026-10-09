<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $blocks = $request->user()->blocks()->with('blocked:id,name')->get()
            ->map(fn ($b) => ['user_id' => $b->blocked_id, 'name' => $b->blocked?->name]);

        return response()->json(['data' => $blocks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'string', 'exists:profiles,public_id'],
        ]);

        $profile = Profile::where('public_id', $validated['profile_id'])->firstOrFail();

        abort_if($profile->user_id === $request->user()->id, 422, 'You cannot block yourself.');

        $request->user()->blocks()->firstOrCreate(['blocked_id' => $profile->user_id]);

        return response()->json(['message' => 'User blocked.'], 201);
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $profile = Profile::where('public_id', $publicId)->firstOrFail();
        $request->user()->blocks()->where('blocked_id', $profile->user_id)->delete();

        return response()->json(['message' => 'User unblocked.']);
    }
}
