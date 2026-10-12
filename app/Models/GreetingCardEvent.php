<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GreetingCardEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'card_id',
        'event_type',
        'session_id',
        'referrer_category',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(GreetingCard::class, 'card_id');
    }
}
