<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

/**
 * Permissions are checked by policy, never by role name alone (blueprint §5).
 * A manager row carries granular can_* flags.
 */
class ProfilePolicy
{
    public function view(?User $user, Profile $profile): bool
    {
        return true; // visibility of individual fields is handled by PrivacyResolver
    }

    public function update(User $user, Profile $profile): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $this->managerCan($user, $profile, 'can_edit');
    }

    public function delete(User $user, Profile $profile): bool
    {
        // Only the candidate themselves (the self manager) may close the profile.
        return $profile->user_id === $user->id || $user->isAdmin();
    }

    public function manage(User $user, Profile $profile): bool
    {
        return $profile->user_id === $user->id || $user->isAdmin();
    }

    private function managerCan(User $user, Profile $profile, string $permission): bool
    {
        $manager = $profile->relationLoaded('managers')
            ? $profile->managers->firstWhere('user_id', $user->id)
            : $profile->managers()->where('user_id', $user->id)->first();

        return (bool) ($manager?->{$permission} ?? false);
    }
}
