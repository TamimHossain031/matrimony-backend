<?php

namespace App\Models;

use App\Enums\Importance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferenceWeight extends Model
{
    protected $fillable = ['partner_preference_id', 'preference_key', 'importance'];

    protected function casts(): array
    {
        return ['importance' => Importance::class];
    }

    public function partnerPreference(): BelongsTo
    {
        return $this->belongsTo(PartnerPreference::class);
    }
}
