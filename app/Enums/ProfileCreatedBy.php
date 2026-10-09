<?php

namespace App\Enums;

enum ProfileCreatedBy: string
{
    case Self_ = 'self';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Relative = 'relative';
    case Guardian = 'guardian';
}
