<?php

namespace App\Services;

use App\Enums\ManagerRelationship;
use App\Exceptions\DomainException;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and updates profiles, keeping the derived state (completion, has_photo)
 * and the required satellite rows (self-manager, privacy settings) consistent.
 */
class ProfileService
{
    public function __construct(
        private readonly ProfileCompletionService $completion,
        private readonly EntitlementService $entitlements,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function createForUser(User $user, array $data): Profile
    {
        $fullNameEn = ! blank($data['full_name_en'] ?? null)
            ? $data['full_name_en']
            : $user->name;

        if (blank($fullNameEn)) {
            throw new DomainException('Full name (English) is required to create a profile.');
        }

        $gender = $data['gender'] ?? $user->gender?->value;
        if (blank($gender)) {
            throw new DomainException('Gender is required to create a profile.');
        }

        $dateOfBirth = $data['date_of_birth'] ?? $user->date_of_birth;
        if (blank($dateOfBirth)) {
            throw new DomainException('Date of birth is required to create a profile.');
        }

        return DB::transaction(function () use ($user, $data, $fullNameEn, $gender, $dateOfBirth) {
            $profile = Profile::create(array_merge($data, [
                'user_id' => $user->id,
                'public_id' => $this->generatePublicId(),
                'full_name_en' => $fullNameEn,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
            ]));

            // Every profile has exactly one `self` manager (the candidate), unless
            // explicitly guardian-managed (blueprint §5).
            $profile->managers()->create([
                'user_id' => $user->id,
                'relationship' => ManagerRelationship::Self_,
                'can_edit' => true,
                'can_shortlist' => true,
                'can_send_interest' => true,
                'can_chat' => true,
            ]);

            $profile->privacySettings()->create([]);
            $this->entitlements->ensure($user);
            $this->completion->sync($profile);

            return $profile->fresh();
        });
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public function update(Profile $profile, array $data): Profile
    {
        $profile->update($data);
        $this->completion->sync($profile);

        return $profile->fresh();
    }

    /** Non-sequential public Profile ID (blueprint §4). */
    public function generatePublicId(): string
    {
        do {
            $id = 'BD-'.strtoupper(Str::random(8));
        } while (Profile::where('public_id', $id)->exists());

        return $id;
    }
}
