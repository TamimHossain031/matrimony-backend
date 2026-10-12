<?php

namespace App\Http\Resources;

use App\Enums\PhotoModerationStatus;
use App\Enums\PhotoRequestStatus;
use App\Enums\Visibility;
use App\Models\PhotoRequest;
use App\Services\PrivacyResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Full profile detail. Sensitive fields are gated through PrivacyResolver using
 * the profile's privacy settings (blueprint §11) — the backend enforces this.
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $resolver = app(PrivacyResolver::class);
        $privacy = $this->privacySettings;
        $isOwner = $viewer && $resolver->isOwner($viewer, $this->resource);

        return [
            'public_id' => $this->public_id,
            'is_owner' => $isOwner,
            'full_name_en' => $this->full_name_en,
            'full_name_bn' => $this->full_name_bn,
            'gender' => $this->gender?->value,
            'age' => $this->age,
            'height_cm' => $this->height_cm,
            'marital_status' => $this->marital_status,
            'nationality' => $this->nationality,
            'headline' => $this->headline,
            'about' => $this->about,
            'completion_percent' => $this->completion_percent,
            'verification_level' => $this->verification_level,

            'district' => $this->whenLoaded('district', fn () => $this->district?->only(['id', 'name_en', 'name_bn'])),
            'upazila' => $this->whenLoaded('upazila', fn () => $this->upazila?->only(['id', 'name_en', 'name_bn'])),
            'religion' => $this->whenLoaded('religion', fn () => $this->religion?->only(['id', 'key', 'name_en'])),
            'profession' => $this->whenLoaded('profession', fn () => $this->profession?->only(['id', 'key', 'name_en'])),
            'education_level' => $this->whenLoaded('educationLevel', fn () => $this->educationLevel?->only(['id', 'key', 'name_en'])),

            'education' => [
                'institution' => $this->institution,
                'subject' => $this->subject,
                'graduation_year' => $this->graduation_year,
                'history' => $this->whenLoaded('educations', fn () => $this->educations),
            ],

            'career' => [
                'job_title' => $this->job_title,
                'organization' => $this->organization,
                'income_range' => [$this->income_min, $this->income_max],
                'work_location' => $this->work_location,
            ],

            'family' => [
                'father_occupation' => $this->father_occupation,
                'mother_occupation' => $this->mother_occupation,
                'siblings_count' => $this->siblings_count,
                'family_type' => $this->family_type,
                'family_location' => $this->family_location,
                'description' => $this->family_description,
            ],

            'lifestyle' => $this->lifestyle,
            'religion_attributes' => $this->whenLoaded('religionAttributes', fn () => $this->religionAttributes->pluck('value', 'key')),

            'managers' => $this->whenLoaded('managers', fn () => $this->managers->map(fn ($m) => [
                'relationship' => $m->relationship?->value,
            ])),

            // Sensitive contact fields — gated by privacy settings.
            'contact' => $this->when($privacy !== null, fn () => array_filter([
                'phone' => $this->gateField($resolver, $viewer, $privacy?->phone_visibility, $this->user?->phone),
                'email' => $this->gateField($resolver, $viewer, $privacy?->email_visibility, $this->user?->email),
            ], fn ($v) => $v !== null)),

            'photos' => ProfilePhotoResource::collection($this->whenLoaded('photos')),
            'avatar_url' => $this->whenLoaded('photos', function () use ($isOwner, $viewer, $resolver) {
                $primaryPhoto = $this->photos->firstWhere('is_primary', true) ?? $this->photos->first();
                if (! $primaryPhoto) {
                    return null;
                }
                $canSee = $isOwner || ($primaryPhoto->moderation_status === PhotoModerationStatus::Approved && (
                    PhotoRequest::where('profile_id', $this->id)
                        ->where('requester_id', $viewer?->id)
                        ->where('status', PhotoRequestStatus::Approved->value)
                        ->exists()
                    || $resolver->canSee($viewer, $this->resource, $primaryPhoto->visibility)
                ));

                return $canSee ? URL::temporarySignedRoute('api.v1.photos.show', now()->addHours(24), ['photo' => $primaryPhoto->id]) : null;
            }),
        ];
    }

    private function gateField(PrivacyResolver $resolver, $viewer, ?Visibility $level, $value)
    {
        if ($value === null || $level === null) {
            return null;
        }

        return $resolver->canSee($viewer, $this->resource, $level) ? $value : null;
    }
}
