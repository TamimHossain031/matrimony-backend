<?php

namespace App\Enums;

enum ManagerRelationship: string
{
    case Self_ = 'self';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Relative = 'relative';
}
