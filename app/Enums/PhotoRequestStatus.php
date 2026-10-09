<?php

namespace App\Enums;

enum PhotoRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';
}
