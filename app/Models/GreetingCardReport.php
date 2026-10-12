<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GreetingCardReport extends Model
{
    protected $fillable = [
        'card_id',
        'reason',
        'details',
        'status',
        'reporter_ip_hash',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(GreetingCard::class, 'card_id');
    }
}
