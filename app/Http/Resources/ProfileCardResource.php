<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape for the profile card (blueprint §7). Never includes sensitive fields —
 * the API resource is shaped for the card, not the full model.
 *
 * A `match` array (score + explanation) may be attached by the discovery
 * controller via `->additional()` or by setting $resource->match.
 */
class ProfileCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->displayName(),
            'age' => $this->age,
            'gender' => $this->gender?->value,
            'district' => $this->whenLoaded('district', fn () => $this->district?->name_en),
            'profession' => $this->whenLoaded('profession', fn () => $this->profession?->name_en),
            'education' => $this->whenLoaded('educationLevel', fn () => $this->educationLevel?->name_en),
            'headline' => $this->headline,
            'verification_level' => $this->verification_level,
            'has_photo' => (bool) $this->has_photo,
            'primary_photo' => $this->when(
                $this->relationLoaded('photos'),
                fn () => $this->primaryPhoto(),
            ),
            'match' => $this->when(isset($this->match), fn () => $this->match),
        ];
    }

    private function primaryPhoto(): ?ProfilePhotoResource
    {
        $photo = $this->photos->firstWhere('is_primary', true);

        if ($photo === null) {
            return null;
        }

        $photo->setRelation('profile', $this->resource);

        return new ProfilePhotoResource($photo);
    }

    /**
     * Show initials rather than the full name until there's a connection, to
     * keep list responses privacy-preserving.
     */
    private function displayName(): string
    {
        $name = (string) $this->full_name_en;
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $initials !== '' ? $initials : 'User';
    }
}
