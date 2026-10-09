<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Guardian = 'guardian';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function isStaff(): bool
    {
        return in_array($this, [self::Moderator, self::Admin], true);
    }
}
