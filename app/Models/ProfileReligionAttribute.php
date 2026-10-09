<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileReligionAttribute extends Model
{
    protected $fillable = ['profile_id', 'key', 'value'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
