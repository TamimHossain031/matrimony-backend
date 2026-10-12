<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\Gender;
use App\Enums\ProfileCreatedBy;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A unique, valid BD mobile number.
        $digits = '1'.fake()->numberBetween(3, 9).fake()->numerify('########');
        $e164 = Phone::normalizeBd($digits);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '0'.$digits,
            'phone_e164' => $e164,
            'phone_hash' => $e164 ? Phone::hash($e164) : null,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::User,
            'status' => AccountStatus::Active,
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-25 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([Gender::Male, Gender::Female]),
            'profile_created_by' => ProfileCreatedBy::Self_,
            'terms_accepted_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function male(): static
    {
        return $this->state(fn () => ['gender' => Gender::Male, 'date_of_birth' => '1992-01-01']);
    }

    public function female(): static
    {
        return $this->state(fn () => ['gender' => Gender::Female, 'date_of_birth' => '1996-01-01']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function moderator(): static
    {
        return $this->state(fn () => ['role' => UserRole::Moderator]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => AccountStatus::Suspended]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
