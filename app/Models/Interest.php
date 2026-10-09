<?php

namespace App\Models;

use App\Enums\InterestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interest extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'status', 'message', 'expires_at', 'responded_at'];

    protected function casts(): array
    {
        return [
            'status' => InterestStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InterestEvent::class);
    }
}
