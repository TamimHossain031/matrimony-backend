<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PhotoRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Photo request flow (§12): viewer requests → owner approves/denies → viewer
 * sees the unblurred image (resolved via visibility once approved).
 */
class PhotoRequestController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** A viewer requests access to a profile's photos. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'string', 'exists:profiles,public_id'],
        ]);

        $profile = Profile::where('public_id', $validated['profile_id'])->firstOrFail();
        abort_if($profile->user_id === $request->user()->id, 422, 'These are your own photos.');

        $photoRequest = PhotoRequest::updateOrCreate(
            ['profile_id' => $profile->id, 'requester_id' => $request->user()->id],
            ['status' => PhotoRequestStatus::Pending, 'responded_at' => null],
        );

        $this->notifications->push($profile->user, 'photo_request', 'Photo request', 'Someone requested to see your photos.', ['photo_request_id' => $photoRequest->id]);

        return response()->json(['message' => 'Photo request sent.'], 201);
    }

    /** The owner approves or denies a request. */
    public function update(Request $request, PhotoRequest $photoRequest): JsonResponse
    {
        abort_unless($photoRequest->profile->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,deny'],
        ]);

        $status = $validated['action'] === 'approve' ? PhotoRequestStatus::Approved : PhotoRequestStatus::Denied;
        $photoRequest->update(['status' => $status, 'responded_at' => now()]);

        return response()->json(['message' => "Request {$status->value}."]);
    }
}
