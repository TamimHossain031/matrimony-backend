<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerPreference extends Model
{
    protected $fillable = [
        'profile_id', 'age_min', 'age_max', 'height_min_cm', 'height_max_cm',
        'marital_statuses', 'religion_id', 'min_education_level_id',
        'profession_ids', 'income_min', 'district_ids', 'other_expectations',
    ];

    protected function casts(): array
    {
        return [
            'marital_statuses' => 'array',
            'profession_ids' => 'array',
            'district_ids' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(PreferenceWeight::class);
    }
}
