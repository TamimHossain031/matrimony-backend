<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfileContactBlock;
use App\Support\Phone;

/**
 * Relative blocklist (differentiator D2, blueprint §11). Phone numbers are
 * normalized then hashed; the raw number is never stored.
 */
class ContactBlockService
{
    /**
     * Add a phone number to a profile's blocklist. Returns null if the number
     * is not a recognizable BD mobile number.
     */
    public function add(Profile $profile, string $rawPhone, ?string $label = null): ?ProfileContactBlock
    {
        $e164 = Phone::normalizeBd($rawPhone);
        if ($e164 === null) {
            return null;
        }

        return ProfileContactBlock::firstOrCreate(
            ['profile_id' => $profile->id, 'phone_hash' => Phone::hash($e164)],
            ['label' => $label, 'created_at' => now()],
        );
    }

    /**
     * @param  iterable<string>  $rawPhones
     * @return array{added:int, invalid:list<string>}
     */
    public function addMany(Profile $profile, iterable $rawPhones): array
    {
        $added = 0;
        $invalid = [];
        foreach ($rawPhones as $raw) {
            if ($this->add($profile, (string) $raw)) {
                $added++;
            } else {
                $invalid[] = (string) $raw;
            }
        }

        return ['added' => $added, 'invalid' => $invalid];
    }
}
