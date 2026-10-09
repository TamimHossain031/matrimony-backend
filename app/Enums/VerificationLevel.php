<?php

namespace App\Enums;

/**
 * Verification ladder. Blueprint §15.
 */
enum VerificationLevel: int
{
    case Unverified = 0;
    case PhoneEmail = 1;   // phone + email verified
    case Nid = 2;          // NID verified
    case Selfie = 3;       // selfie matched to NID photo
    case Manual = 4;       // manually reviewed by admin

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::PhoneEmail => 'Phone + email verified',
            self::Nid => 'NID verified',
            self::Selfie => 'Selfie matched to NID',
            self::Manual => 'Manually reviewed',
        };
    }
}
