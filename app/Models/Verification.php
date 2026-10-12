<?php

namespace App\Models;

use App\Enums\VerificationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    protected $fillable = [
        'user_id', 'level', 'status', 'document_path',
        'reviewed_by', 'review_note', 'reviewed_at',
    ];

    protected $hidden = ['document_path'];

    protected function casts(): array
    {
        return [
            'level' => VerificationLevel::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
