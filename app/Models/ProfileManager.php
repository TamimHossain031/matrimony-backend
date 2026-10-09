<?php

namespace App\Models;

use App\Enums\ManagerRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileManager extends Model
{
    protected $fillable = [
        'profile_id', 'user_id', 'relationship',
        'can_edit', 'can_shortlist', 'can_send_interest', 'can_chat',
    ];

    protected function casts(): array
    {
        return [
            'relationship' => ManagerRelationship::class,
            'can_edit' => 'boolean',
            'can_shortlist' => 'boolean',
            'can_send_interest' => 'boolean',
            'can_chat' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSelf(): bool
    {
        return $this->relationship === ManagerRelationship::Self_;
    }
}
