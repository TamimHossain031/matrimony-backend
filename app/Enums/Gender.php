<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    /** Minimum legal marriage age in Bangladesh (blueprint §3). */
    public function minMarriageAge(): int
    {
        return match ($this) {
            self::Male => 21,
            self::Female => 18,
        };
    }
}
