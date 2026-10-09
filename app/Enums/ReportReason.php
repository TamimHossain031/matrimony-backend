<?php

namespace App\Enums;

enum ReportReason: string
{
    case FakeProfile = 'fake_profile';
    case Harassment = 'harassment';
    case Scam = 'scam';
    case InappropriateContent = 'inappropriate_content';
    case MisleadingInformation = 'misleading_information';
    case Other = 'other';
}
