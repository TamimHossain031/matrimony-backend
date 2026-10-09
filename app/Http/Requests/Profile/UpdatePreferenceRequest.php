<?php

namespace App\Http\Requests\Profile;

use App\Enums\Importance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'age_min' => ['nullable', 'integer', 'between:18,80'],
            'age_max' => ['nullable', 'integer', 'between:18,80', 'gte:age_min'],
            'height_min_cm' => ['nullable', 'integer', 'between:120,230'],
            'height_max_cm' => ['nullable', 'integer', 'between:120,230', 'gte:height_min_cm'],
            'marital_statuses' => ['nullable', 'array'],
            'marital_statuses.*' => [Rule::in(['never_married', 'divorced', 'widowed', 'separated'])],
            'religion_id' => ['nullable', 'exists:religions,id'],
            'min_education_level_id' => ['nullable', 'exists:education_levels,id'],
            'profession_ids' => ['nullable', 'array'],
            'profession_ids.*' => ['integer', 'exists:professions,id'],
            'income_min' => ['nullable', 'integer', 'min:0'],
            'district_ids' => ['nullable', 'array'],
            'district_ids.*' => ['integer', 'exists:locations,id'],
            'other_expectations' => ['nullable', 'string', 'max:2000'],

            // Deal-breaker vs nice-to-have per preference key (D3).
            'weights' => ['nullable', 'array'],
            'weights.*.preference_key' => ['required_with:weights', 'string', Rule::in([
                'age_range', 'height_range', 'religion', 'education',
                'profession', 'income', 'district', 'marital_status',
            ])],
            'weights.*.importance' => ['required_with:weights', Rule::enum(Importance::class)],
        ];
    }
}
