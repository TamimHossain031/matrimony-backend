<?php

namespace App\Services;

use App\Enums\InterestStatus;
use App\Enums\Visibility;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;

/**
 * Resolves whether a viewer may see a sensitive field on a profile, from the
 * profile's privacy settings (blueprint §11). The backend enforces this; hiding
 * a field in React is not security.
 */
class PrivacyResolver
{
    /** Is $viewer allowed to see a field with the given visibility level? */
    public function canSee(?User $viewer, Profile $profile, Visibility $level): bool
    {
        return match ($level) {
            Visibility::Public => true,
            Visibility::MembersOnly => $viewer !== null,
            Visibility::AcceptedConnection => $viewer !== null
                && ($this->isOwner($viewer, $profile) || $this->hasAcceptedConnection($viewer, $profile)),
            Visibility::Private_ => $viewer !== null && $this->isOwner($viewer, $profile),
        };
    }

    public function isOwner(User $viewer, Profile $profile): bool
    {
        if ($profile->user_id === $viewer->id) {
            return true;
        }

        // A manager with edit rights counts as owner for visibility purposes.
        return $profile->managers
            ->firstWhere('user_id', $viewer->id)?->can_edit ?? false;
    }

    public function hasAcceptedConnection(User $viewer, Profile $profile): bool
    {
        $owner = $profile->user_id;

        return Interest::where('status', InterestStatus::Accepted->value)
            ->where(function ($q) use ($viewer, $owner) {
                $q->where(function ($x) use ($viewer, $owner) {
                    $x->where('sender_id', $viewer->id)->where('receiver_id', $owner);
                })->orWhere(function ($x) use ($viewer, $owner) {
                    $x->where('sender_id', $owner)->where('receiver_id', $viewer->id);
                });
            })
            ->exists();
    }
}
