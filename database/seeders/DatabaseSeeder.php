<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Gender;
use App\Enums\ProfileCreatedBy;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LookupSeeder::class);

        // An admin account for moderation (blueprint §16).
        $this->createUser(
            name: 'Admin',
            email: 'admin@matrimony.test',
            phone: '01700000000',
            role: UserRole::Admin,
            gender: Gender::Male,
            dob: '1985-01-01',
        );

        $this->call(BangladeshUserSeeder::class);
    }

    private function createUser(string $name, string $email, string $phone, UserRole $role, Gender $gender, string $dob): void
    {
        $e164 = Phone::normalizeBd($phone);

        User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'phone' => $phone,
            'phone_e164' => $e164,
            'phone_hash' => $e164 ? Phone::hash($e164) : null,
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => AccountStatus::Active,
            'date_of_birth' => $dob,
            'gender' => $gender,
            'profile_created_by' => ProfileCreatedBy::Self_,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'terms_accepted_at' => now(),
        ]);
    }
}
