<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\User;

/**
 * Entitlements abstraction (blueprint §14). No payments in the MVP — the quotas
 * are the anti-spam system today and the paid tier later.
 */
class EntitlementService
{
    public function ensure(User $user): Entitlement
    {
        return $user->entitlement()->firstOrCreate([], [
            'plan' => 'free',
            'daily_interest_limit' => config('matrimony.entitlements.free.daily_interest_limit', 10),
            'daily_contact_view_limit' => config('matrimony.entitlements.free.daily_contact_view_limit', 20),
        ]);
    }

    public function dailyInterestLimit(User $user): int
    {
        return $this->ensure($user)->daily_interest_limit;
    }

    public function dailyContactViewLimit(User $user): int
    {
        return $this->ensure($user)->daily_contact_view_limit;
    }
}
