<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PhotoModerationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateBlurredPhoto;
use App\Models\AuditLog;
use App\Models\ProfilePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPhotoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProfilePhoto::with('profile.user')->latest();

        if ($request->filled('moderation_status')) {
            $query->where('moderation_status', $request->input('moderation_status'));
        }

        $photos = $query->paginate(24);

        return response()->json($photos);
    }

    public function updateModeration(Request $request, ProfilePhoto $photo): JsonResponse
    {
        $validated = $request->validate([
            'moderation_status' => ['required', 'in:approved,rejected,pending'],
        ]);

        $status = PhotoModerationStatus::from($validated['moderation_status']);
        $photo->update(['moderation_status' => $status]);

        if ($status === PhotoModerationStatus::Approved) {
            if (! $photo->blurred_path) {
                GenerateBlurredPhoto::dispatch($photo);
            }
            $photo->profile->forceFill(['has_photo' => true])->saveQuietly();
        } else {
            $hasOtherApproved = $photo->profile->photos()
                ->where('id', '!=', $photo->id)
                ->where('moderation_status', PhotoModerationStatus::Approved)
                ->exists();
            $photo->profile->forceFill(['has_photo' => $hasOtherApproved])->saveQuietly();
        }

        AuditLog::record(
            actor: $request->user(),
            action: "photo.{$status->value}",
            subject: $photo,
            context: ['profile_id' => $photo->profile_id]
        );

        return response()->json([
            'message' => "Photo moderation status updated to {$status->value}.",
            'data' => $photo->fresh('profile'),
        ]);
    }

    public function batchAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'photo_ids' => ['required', 'array'],
            'photo_ids.*' => ['exists:profile_photos,id'],
        ]);

        $status = $validated['action'] === 'approve'
            ? PhotoModerationStatus::Approved
            : PhotoModerationStatus::Rejected;

        $photos = ProfilePhoto::with('profile')->whereIn('id', $validated['photo_ids'])->get();

        foreach ($photos as $photo) {
            $photo->update(['moderation_status' => $status]);

            if ($status === PhotoModerationStatus::Approved) {
                if (! $photo->blurred_path) {
                    GenerateBlurredPhoto::dispatch($photo);
                }
                $photo->profile->forceFill(['has_photo' => true])->saveQuietly();
            }
        }

        AuditLog::record(
            actor: $request->user(),
            action: "photo.batch_{$validated['action']}",
            context: ['photo_ids' => $validated['photo_ids'], 'count' => count($photos)]
        );

        return response()->json([
            'message' => "Batch {$validated['action']} applied to ".count($photos).' photos.',
        ]);
    }

    public function destroy(Request $request, ProfilePhoto $photo): JsonResponse
    {
        Storage::disk($photo->disk)->delete(array_filter([$photo->path, $photo->blurred_path]));
        $profile = $photo->profile;

        $photo->delete();

        $hasApproved = $profile->photos()
            ->where('moderation_status', PhotoModerationStatus::Approved)
            ->exists();
        $profile->forceFill(['has_photo' => $hasApproved])->saveQuietly();

        AuditLog::record(
            actor: $request->user(),
            action: 'photo.deleted',
            subject: $photo,
            context: ['profile_id' => $profile->id]
        );

        return response()->json(['message' => 'Photo deleted successfully.']);
    }
}
