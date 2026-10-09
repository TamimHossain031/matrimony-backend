<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterestResource;
use App\Models\Interest;
use App\Models\Profile;
use App\Services\InterestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    public function __construct(private readonly InterestService $interests) {}

    /** Send an interest to a profile (identified by its public ID). */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'string', 'exists:profiles,public_id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $receiverProfile = Profile::where('public_id', $validated['profile_id'])->firstOrFail();

        $interest = $this->interests->send(
            $request->user(),
            $receiverProfile->user,
            $validated['message'] ?? null,
        );

        return response()->json([
            'data' => new InterestResource($interest),
        ], 201);
    }

    public function received(Request $request): JsonResponse
    {
        $interests = $request->user()->receivedInterests()
            ->whereIn('status', ['pending', 'accepted'])
            ->with(['sender.profile.district', 'sender.profile.profession', 'sender.profile.educationLevel'])
            ->latest()
            ->cursorPaginate(20);

        return $this->paginated($interests);
    }

    public function sent(Request $request): JsonResponse
    {
        $interests = $request->user()->sentInterests()
            ->with(['receiver.profile.district', 'receiver.profile.profession', 'receiver.profile.educationLevel'])
            ->latest()
            ->cursorPaginate(20);

        return $this->paginated($interests);
    }

    /** Accept / reject / withdraw / disconnect. */
    public function update(Request $request, Interest $interest): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:accept,reject,withdraw,disconnect'],
        ]);

        $user = $request->user();

        $interest = match ($validated['action']) {
            'accept' => $this->interests->accept($interest, $user),
            'reject' => $this->interests->reject($interest, $user),
            'withdraw' => $this->interests->withdraw($interest, $user),
            'disconnect' => $this->interests->disconnect($interest, $user),
        };

        return response()->json(['data' => new InterestResource($interest)]);
    }

    private function paginated($paginator): JsonResponse
    {
        return response()->json([
            'data' => InterestResource::collection($paginator->items()),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
        ]);
    }
}
