<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'public_id' => 'BD-'.strtoupper(Str::random(8)),
            'full_name_en' => fake()->name(),
            'gender' => fake()->randomElement([Gender::Male, Gender::Female]),
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-25 years')->format('Y-m-d'),
            'height_cm' => fake()->numberBetween(150, 185),
            'marital_status' => 'never_married',
            'nationality' => 'Bangladeshi',
            'headline' => fake()->sentence(4),
        ];
    }

    /** Create the profile for a given user, keeping gender/dob consistent. */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth,
        ]);
    }
}
