<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Gender;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The matrimony profile. Every searchable field lives here, indexed (§13).
 */
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'public_id',
        'full_name_en', 'full_name_bn', 'gender', 'date_of_birth', 'height_cm',
        'marital_status', 'district_id', 'upazila_id', 'nationality',
        'headline', 'about', 'religion_id',
        'education_level_id', 'institution', 'subject', 'graduation_year',
        'profession_id', 'job_title', 'organization', 'income_min', 'income_max', 'work_location',
        'father_occupation', 'mother_occupation', 'siblings_count', 'family_type',
        'family_location', 'family_description', 'lifestyle',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'lifestyle' => 'array',
            'has_photo' => 'boolean',
        ];
    }

    protected $appends = ['age'];

    // ---- Derived -----------------------------------------------------------

    /** Age is always computed from date_of_birth, never stored (§4). */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    // ---- Relationships -----------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(ProfileManager::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'upazila_id');
    }

    public function religionAttributes(): HasMany
    {
        return $this->hasMany(ProfileReligionAttribute::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('position');
    }

    public function partnerPreference(): HasOne
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function privacySettings(): HasOne
    {
        return $this->hasOne(PrivacySetting::class);
    }

    public function contactBlocks(): HasMany
    {
        return $this->hasMany(ProfileContactBlock::class);
    }

    // ---- Scopes ------------------------------------------------------------

    /** Only profiles whose owning account is active and discoverable (§4, §7). */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query->whereHas('user', function (Builder $q) {
            $q->where('status', AccountStatus::Active->value)
                ->whereNull('deleted_at');
        });
    }
}
