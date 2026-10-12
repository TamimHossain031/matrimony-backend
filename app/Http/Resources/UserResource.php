<?php

namespace App\Http\Resources;

use App\Models\ProfilePhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryPhoto = $this->primaryPhoto();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'status' => $this->status?->value,
            'gender' => $this->gender?->value,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age(),
            'profile_created_by' => $this->profile_created_by?->value,
            'email_verified' => $this->email_verified_at !== null,
            'phone_verified' => $this->phone_verified_at !== null,
            'has_profile' => $this->whenLoaded('profile', fn () => $this->profile !== null),
            'avatar_url' => $primaryPhoto ? URL::temporarySignedRoute('api.v1.photos.show', now()->addHours(24), ['photo' => $primaryPhoto->id]) : null,
        ];
    }

    private function primaryPhoto(): ?ProfilePhoto
    {
        $profile = $this->relationLoaded('profile') ? $this->profile : $this->profile()->first();
        if (! $profile) {
            return null;
        }

        if ($profile->relationLoaded('photos')) {
            return $profile->photos->firstWhere('is_primary', true) ?? $profile->photos->first();
        }

        return $profile->photos()->where('is_primary', true)->first()
            ?? $profile->photos()->first();
    }
}
