<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProfileContactBlock;
use App\Services\ContactBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The relative blocklist (D2). Owners add phone numbers that must never see
 * their profile; the numbers are hashed and never returned.
 */
class ContactBlockController extends Controller
{
    public function __construct(private readonly ContactBlockService $service) {}

    public function index(Request $request): JsonResponse
    {
        $profile = $this->profileOrFail($request);

        $blocks = $profile->contactBlocks()
            ->get(['id', 'label', 'created_at']); // never the hash

        return response()->json(['data' => $blocks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phones' => ['required', 'array', 'min:1'],
            'phones.*' => ['required', 'string', 'max:20'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $profile = $this->profileOrFail($request);

        $result = $this->service->addMany($profile, $validated['phones']);

        return response()->json([
            'message' => "{$result['added']} number(s) added to your blocklist.",
            'invalid' => $result['invalid'],
        ], 201);
    }

    public function destroy(Request $request, ProfileContactBlock $contactBlock): JsonResponse
    {
        $profile = $this->profileOrFail($request);

        abort_unless($contactBlock->profile_id === $profile->id, 403, 'This action is unauthorized.');

        $contactBlock->delete();

        return response()->json(['message' => 'Removed from blocklist.']);
    }

    private function profileOrFail(Request $request)
    {
        $profile = $request->user()->profile;
        abort_if($profile === null, 404, 'Create a profile first.');

        return $profile;
    }
}
