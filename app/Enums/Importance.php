<?php

namespace App\Enums;

/**
 * Preference weight class — the heart of the transparent matching feature (D3).
 */
enum Importance: string
{
    case DealBreaker = 'deal_breaker';
    case NiceToHave = 'nice_to_have';
}
