<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['interest_id', 'actor_id', 'event', 'from_status', 'to_status', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class);
    }
}
