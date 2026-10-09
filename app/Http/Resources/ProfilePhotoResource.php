<?php

namespace App\Http\Resources;

use App\Enums\PhotoModerationStatus;
use App\Services\PrivacyResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * A photo. The real (unblurred) signed URL is only emitted when the viewer is
 * allowed to see it; otherwise the client receives a blurred flag (§12).
 */
class ProfilePhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $resolver = app(PrivacyResolver::class);
        $canSee = $this->moderation_status === PhotoModerationStatus::Approved
            && $viewer !== null
            && $resolver->canSee($viewer, $this->profile, $this->visibility);

        return [
            'id' => $this->id,
            'is_primary' => $this->is_primary,
            'position' => $this->position,
            'moderation_status' => $this->moderation_status?->value,
            'blurred' => ! $canSee,
            // Signed, expiring URL — private photos are never reachable by guessing.
            'url' => $canSee ? $this->signedUrl() : null,
        ];
    }

    private function signedUrl(): string
    {
        return URL::temporarySignedRoute(
            'api.v1.photos.show',
            now()->addMinutes(15),
            ['photo' => $this->id],
        );
    }
}
