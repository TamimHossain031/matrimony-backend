<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The relative blocklist (D2). Stores only a salted hash of a phone number.
 */
class ProfileContactBlock extends Model
{
    public $timestamps = false;

    protected $fillable = ['profile_id', 'phone_hash', 'label', 'created_at'];

    protected $hidden = ['phone_hash'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
