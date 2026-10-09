<?php

namespace App\Models;

use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacySetting extends Model
{
    protected $fillable = [
        'profile_id', 'phone_visibility', 'email_visibility',
        'address_visibility', 'family_contact_visibility', 'photo_visibility',
    ];

    protected function casts(): array
    {
        return [
            'phone_visibility' => Visibility::class,
            'email_visibility' => Visibility::class,
            'address_visibility' => Visibility::class,
            'family_contact_visibility' => Visibility::class,
            'photo_visibility' => Visibility::class,
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
