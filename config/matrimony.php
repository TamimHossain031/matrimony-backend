<?php

/**
 * Domain rules for the matrimony platform.
 * Keeping these in config (not scattered in controllers) follows the
 * blueprint's architecture principle: domain rules live in one place.
 */
return [
    // Minimum legal marriage age in Bangladesh (blueprint §3).
    'min_age' => [
        'male' => 21,
        'female' => 18,
    ],

    'interests' => [
        // Interest auto-expires after N days with no response (D6 hook).
        'expire_after_days' => 7,
        // Cooldown after a rejection before the same sender may try again.
        'rejection_cooldown_days' => 30,
        // Cooldown after a disconnect before a new interest is allowed.
        'disconnect_cooldown_days' => 30,
    ],

    // Default free-plan entitlements (blueprint §14). Doubles as anti-spam.
    'entitlements' => [
        'free' => [
            'daily_interest_limit' => 10,
            'daily_contact_view_limit' => 20,
        ],
    ],

    'matching' => [
        // A single failed deal-breaker multiplies the raw score by this factor.
        'deal_breaker_penalty' => 0.15,
    ],

    'photos' => [
        'max_size_kb' => 5120,
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_per_profile' => 10,
    ],

    // Salt used when hashing relative-blocklist phone numbers at rest (D2).
    // Falls back to APP_KEY-derived value; override in production.
    'contact_block_pepper' => env('CONTACT_BLOCK_PEPPER', null),
];
