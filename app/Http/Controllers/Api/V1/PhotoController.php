<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PhotoModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProfilePhotoResource;
use App\Models\ProfilePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $this->ownProfile($request);

        return response()->json([
            'data' => ProfilePhotoResource::collection($profile->photos),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $profile = $this->ownProfile($request);

        $max = (int) config('matrimony.photos.max_per_profile', 10);
        abort_if($profile->photos()->count() >= $max, 422, "You can upload at most {$max} photos.");

        $request->validate([
            'photo' => [
                'required', 'image',
                'mimes:'.implode(',', config('matrimony.photos.allowed_mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.config('matrimony.photos.max_size_kb', 5120),
                'dimensions:min_width=200,min_height=200',
            ],
            'visibility' => ['sometimes', 'in:public,members_only,accepted_connection,private'],
        ]);

        // Opaque, non-guessable storage key on the private disk.
        $path = $request->file('photo')->store('profile-photos/'.$profile->id, 'local');
        [$width, $height] = @getimagesize($request->file('photo')->getPathname()) ?: [null, null];

        $isFirst = $profile->photos()->count() === 0;

        $photo = $profile->photos()->create([
            'disk' => 'local',
            'path' => $path,
            'is_primary' => $isFirst,
            'position' => $profile->photos()->max('position') + 1,
            'visibility' => $request->input('visibility', 'members_only'),
            'moderation_status' => PhotoModerationStatus::Pending,
            'width' => $width,
            'height' => $height,
        ]);

        $profile->forceFill(['has_photo' => true])->saveQuietly();

        return response()->json(['data' => new ProfilePhotoResource($photo)], 201);
    }

    public function setPrimary(Request $request, ProfilePhoto $photo): JsonResponse
    {
        $this->assertOwnsPhoto($request, $photo);

        $photo->profile->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return response()->json(['message' => 'Primary photo updated.']);
    }

    public function destroy(Request $request, ProfilePhoto $photo): JsonResponse
    {
        $this->assertOwnsPhoto($request, $photo);

        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        $profile = $photo->profile;
        if ($profile->photos()->count() === 0) {
            $profile->forceFill(['has_photo' => false])->saveQuietly();
        }

        return response()->json(['message' => 'Photo deleted.']);
    }

    /**
     * Serve the raw image behind a short-lived signed URL. The 'signed'
     * middleware guarantees the URL was issued by us and hasn't expired, so
     * private photos are never reachable by guessing a path (§12).
     */
    public function show(ProfilePhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk($photo->disk)->exists($photo->path), 404);

        return Storage::disk($photo->disk)->response($photo->path);
    }

    private function ownProfile(Request $request)
    {
        $profile = $request->user()->profile;
        abort_if($profile === null, 404, 'Create a profile first.');

        return $profile;
    }

    private function assertOwnsPhoto(Request $request, ProfilePhoto $photo): void
    {
        abort_unless($photo->profile->user_id === $request->user()->id, 403, 'This action is unauthorized.');
    }
}
