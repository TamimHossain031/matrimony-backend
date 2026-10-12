<?php

namespace App\Models;

use App\Enums\PhotoModerationStatus;
use App\Enums\Visibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfilePhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'profile_id', 'disk', 'path', 'blurred_path', 'is_primary', 'position',
        'visibility', 'moderation_status', 'width', 'height',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'visibility' => Visibility::class,
            'moderation_status' => PhotoModerationStatus::class,
        ];
    }

    protected $hidden = ['path', 'blurred_path', 'disk'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === PhotoModerationStatus::Approved;
    }
}
