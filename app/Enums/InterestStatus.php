<?php

namespace App\Enums;

/**
 * Current-state of an interest between an ordered (sender, receiver) pair.
 * History is preserved in interest_events. Blueprint §8.
 */
enum InterestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
    case Disconnected = 'disconnected';

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Accepted;
    }
}
