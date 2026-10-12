<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'design_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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
