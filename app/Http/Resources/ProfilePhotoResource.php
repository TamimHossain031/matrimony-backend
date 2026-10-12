<?php

namespace App\Http\Resources;

use App\Enums\PhotoModerationStatus;
use App\Enums\PhotoRequestStatus;
use App\Models\PhotoRequest;
use App\Models\User;
use App\Services\PrivacyResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * A photo. The real (unblurred) signed URL is only emitted when the viewer is
 * allowed to see it; everyone else only ever receives the server-blurred copy (§12).
 */
class ProfilePhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSee = $this->viewerCanSeeOriginal($request->user());

        return [
            'id' => $this->id,
            'is_primary' => $this->is_primary,
            'position' => $this->position,
            'visibility' => $this->visibility?->value,
            'moderation_status' => $this->moderation_status?->value,
            'blurred' => ! $canSee,
            // Always safe to show: a server-generated blur, null until the queued job has produced it.
            'blurred_url' => $this->blurred_path ? $this->signedUrl('api.v1.photos.blurred') : null,
            // Signed, expiring URL — private photos are never reachable by guessing.
            'url' => $canSee ? $this->signedUrl('api.v1.photos.show') : null,
        ];
    }

    /**
     * Owners always see their own photos; anyone else needs an approved photo
     * request, or an approved photo whose visibility level they satisfy.
     */
    private function viewerCanSeeOriginal(?User $viewer): bool
    {
        if ($viewer === null) {
            return false;
        }

        $resolver = app(PrivacyResolver::class);

        if ($resolver->isOwner($viewer, $this->profile)) {
            return true;
        }

        if ($this->moderation_status !== PhotoModerationStatus::Approved) {
            return false;
        }

        return PhotoRequest::where('profile_id', $this->profile_id)
            ->where('requester_id', $viewer->id)
            ->where('status', PhotoRequestStatus::Approved->value)
            ->exists()
            || $resolver->canSee($viewer, $this->profile, $this->visibility);
    }

    private function signedUrl(string $route): string
    {
        return URL::temporarySignedRoute($route, now()->addMinutes(15), ['photo' => $this->id]);
    }
}
