<?php

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use App\Enums\ProfileCreatedBy;
use App\Models\User;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'profile_created_by' => ['required', Rule::enum(ProfileCreatedBy::class)],
            'terms_accepted' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize the phone to E.164 up front so the uniqueness check and the
        // "one account per phone number" rule are reliable (blueprint §3).
        $this->merge([
            'phone_e164' => Phone::normalizeBd($this->input('phone')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // One active account per phone number (normalized).
            $e164 = $this->input('phone_e164');
            if ($e164 === null) {
                $validator->errors()->add('phone', 'Enter a valid Bangladeshi mobile number.');
            } elseif (User::where('phone_e164', $e164)->exists()) {
                $validator->errors()->add('phone', 'An account with this phone number already exists.');
            }

            // Minimum legal marriage age in Bangladesh: 18 women, 21 men (§3).
            $gender = Gender::tryFrom((string) $this->input('gender'));
            $dob = $this->input('date_of_birth');
            if ($gender && $dob) {
                $age = Carbon::parse($dob)->age;
                $min = $gender->minMarriageAge();
                if ($age < $min) {
                    $label = $gender === Gender::Female ? 'women' : 'men';
                    $validator->errors()->add('date_of_birth', "The minimum legal marriage age for {$label} in Bangladesh is {$min}.");
                }
            }
        });
    }
}
