<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GreetingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'occasion',
        'description',
        'thumbnail_path',
        'design_config',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'thumbnail_url',
    ];

    protected function casts(): array
    {
        return [
            'design_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (GreetingTemplate $template) {
            if ($template->thumbnail_path && Storage::disk('public')->exists($template->thumbnail_path)) {
                Storage::disk('public')->delete($template->thumbnail_path);
            }
        });
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
            return $this->thumbnail_path;
        }

        if (str_starts_with($this->thumbnail_path, '/storage/')) {
            return url($this->thumbnail_path);
        }

        if (Storage::disk('public')->exists($this->thumbnail_path)) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }

        if (str_starts_with($this->thumbnail_path, '/')) {
            return url($this->thumbnail_path);
        }

        return Storage::disk('public')->url($this->thumbnail_path);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(GreetingCard::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
