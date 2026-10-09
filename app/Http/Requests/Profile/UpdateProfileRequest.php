<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization handled by policy in the controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name_en' => ['sometimes', 'string', 'max:255'],
            'full_name_bn' => ['nullable', 'string', 'max:255'],
            'height_cm' => ['nullable', 'integer', 'between:120,230'],
            'marital_status' => ['nullable', Rule::in(['never_married', 'divorced', 'widowed', 'separated'])],
            'district_id' => ['nullable', 'exists:locations,id'],
            'upazila_id' => ['nullable', 'exists:locations,id'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'headline' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'religion_id' => ['nullable', 'exists:religions,id'],
            'education_level_id' => ['nullable', 'exists:education_levels,id'],
            'institution' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'between:1950,'.(date('Y') + 1)],
            'profession_id' => ['nullable', 'exists:professions,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'income_min' => ['nullable', 'integer', 'min:0'],
            'income_max' => ['nullable', 'integer', 'gte:income_min'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'siblings_count' => ['nullable', 'integer', 'between:0,30'],
            'family_type' => ['nullable', Rule::in(['nuclear', 'joint'])],
            'family_location' => ['nullable', 'string', 'max:255'],
            'family_description' => ['nullable', 'string', 'max:2000'],

            // Lifestyle is a validated JSON blob of low-cardinality detail.
            'lifestyle' => ['nullable', 'array'],
            'lifestyle.smoking' => ['nullable', Rule::in(['no', 'occasionally', 'yes'])],
            'lifestyle.drinking' => ['nullable', Rule::in(['no', 'occasionally', 'yes'])],
            'lifestyle.diet' => ['nullable', Rule::in(['halal', 'vegetarian', 'vegan', 'no_restriction'])],
            'lifestyle.hobbies' => ['nullable', 'array'],
            'lifestyle.hobbies.*' => ['string', 'max:50'],
            'lifestyle.languages' => ['nullable', 'array'],
            'lifestyle.languages.*' => ['string', 'max:50'],

            // Religion attributes: key/value pairs validated per religion in code.
            'religion_attributes' => ['nullable', 'array'],
            'religion_attributes.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
