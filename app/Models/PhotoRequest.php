<?php

namespace App\Models;

use App\Enums\PhotoRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoRequest extends Model
{
    protected $fillable = ['profile_id', 'requester_id', 'status', 'responded_at'];

    protected function casts(): array
    {
        return [
            'status' => PhotoRequestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
