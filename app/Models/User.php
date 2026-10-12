<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Gender;
use App\Enums\ProfileCreatedBy;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * The account: authentication, credentials, role, status (blueprint §4).
 * The matrimony profile is a separate concept, linked via profile_managers.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_e164',
        'phone_hash',
        'password',
        'role',
        'status',
        'date_of_birth',
        'gender',
        'profile_created_by',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'phone_e164',
        'phone_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_active_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'gender' => Gender::class,
            'profile_created_by' => ProfileCreatedBy::class,
        ];
    }

    // ---- Relationships -----------------------------------------------------

    /** Profiles this user manages (as self, parent, sibling, relative). */
    public function managedProfiles(): HasMany
    {
        return $this->hasMany(ProfileManager::class);
    }

    /** The user's own candidate profile (the one they are the subject of). */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(Entitlement::class);
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function reportsSubmitted(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsAgainst(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    // ---- Helpers -----------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role instanceof UserRole && $this->role->isStaff();
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
