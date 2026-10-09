<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    // The pluralizer treats "education" as uncountable, so set the table name.
    protected $table = 'educations';

    protected $fillable = ['profile_id', 'education_level_id', 'institution', 'subject', 'graduation_year'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }
}
