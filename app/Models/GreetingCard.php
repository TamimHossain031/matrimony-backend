<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GreetingCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'edit_token',
        'user_id',
        'template_id',
        'occasion',
        'recipient_name',
        'sender_name',
        'title',
        'message',
        'theme_settings',
        'status',
        'privacy',
        'views_count',
        'shares_count',
        'reactions_count',
        'published_at',
        'expires_at',
    ];

    protected $hidden = [
        'edit_token',
    ];

    protected function casts(): array
    {
        return [
            'theme_settings' => 'array',
            'views_count' => 'integer',
            'shares_count' => 'integer',
            'reactions_count' => 'integer',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(GreetingTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GreetingCardEvent::class, 'card_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(GreetingCardReport::class, 'card_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isOwnedBy(?User $user, ?string $editToken = null): bool
    {
        if ($user && $this->user_id && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        if ($editToken && ! empty($this->edit_token) && hash_equals($this->edit_token, $editToken)) {
            return true;
        }

        return false;
    }
}
