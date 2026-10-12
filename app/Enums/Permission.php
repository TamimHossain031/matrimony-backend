<?php

namespace App\Enums;

/**
 * Fine-grained capabilities that can be granted to individual staff users,
 * on top of their coarse role (blueprint §16). Admins implicitly hold every
 * permission; moderators and other staff hold only what has been granted.
 */
enum Permission: string
{
    case ManageGreetingDesigns = 'manage_greeting_designs';

    /** Human-readable label for admin UIs. */
    public function label(): string
    {
        return match ($this) {
            self::ManageGreetingDesigns => 'Manage Greeting Designs',
        };
    }

    /** Short explanation shown beside the toggle in the admin panel. */
    public function description(): string
    {
        return match ($this) {
            self::ManageGreetingDesigns => 'Create, edit and publish greeting card designs and occasion categories.',
        };
    }
}
