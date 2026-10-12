<?php

namespace App\Enums;

/**
 * Account lifecycle: active → hidden → deactivated → deleted (soft).
 * See blueprint §4.
 */
enum AccountStatus: string
{
    case Active = 'active';
    case Hidden = 'hidden';        // invisible in discovery, connections still work ("I'm engaged")
    case Deactivated = 'deactivated'; // user-initiated pause, login still possible
    case Suspended = 'suspended';    // staff-initiated penalty, login and discovery blocked
    case Deleted = 'deleted';      // soft delete, pending retention purge

    /** Statuses that are allowed to log in. */
    public function canLogin(): bool
    {
        return $this !== self::Deleted && $this !== self::Suspended;
    }

    /** Statuses that appear in discovery / search / recommendations. */
    public function isDiscoverable(): bool
    {
        return $this === self::Active;
    }
}
