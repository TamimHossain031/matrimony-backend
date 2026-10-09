<?php

namespace App\Support;

/**
 * Bangladeshi phone-number normalization to E.164 (+8801XXXXXXXXX).
 *
 * The blueprint requires one active account per phone number, normalized to
 * E.164 (§3), and relative-blocklist matching across both directions (§11, D2).
 * Normalizing on the way in is what makes those rules reliable.
 */
class Phone
{
    /**
     * Normalize a Bangladeshi mobile number to E.164, or return null if it
     * cannot be recognized as a valid BD mobile number.
     *
     * Accepts: 01712345678, +8801712345678, 8801712345678, 1712345678.
     */
    public static function normalizeBd(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Strip everything except digits.
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }

        // Drop a leading country code / trunk prefix down to the 10-digit core
        // that starts with 1 (BD mobile numbers are 1XXXXXXXXX, 10 digits).
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // A valid BD mobile core is 10 digits beginning with 1 (operator 3-9).
        if (! preg_match('/^1[3-9]\d{8}$/', $digits)) {
            return null;
        }

        return '+880'.$digits;
    }

    /**
     * Deterministic, salted hash of a normalized phone number for the relative
     * blocklist. Raw numbers are never stored (blueprint §11).
     */
    public static function hash(string $e164): string
    {
        $pepper = config('matrimony.contact_block_pepper') ?: config('app.key');

        return hash_hmac('sha256', $e164, (string) $pepper);
    }
}
