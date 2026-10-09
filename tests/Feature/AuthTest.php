<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.com',
            'phone' => '01712345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'date_of_birth' => '1998-05-10',
            'gender' => 'female',
            'profile_created_by' => 'self',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'age'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'ayesha@example.com',
            'phone_e164' => '+8801712345678',
        ]);
        // The phone is hashed for the relative blocklist; raw is also stored.
        $this->assertNotNull(User::first()->phone_hash);
    }

    public function test_underage_registration_is_rejected_by_gender(): void
    {
        // 16-year-old woman — below the 18 minimum.
        $this->postJson('/api/v1/auth/register', $this->payload([
            'date_of_birth' => now()->subYears(16)->toDateString(),
        ]))->assertStatus(422)->assertJsonValidationErrors('date_of_birth');

        // 20-year-old man — below the 21 minimum.
        $this->postJson('/api/v1/auth/register', $this->payload([
            'email' => 'man@example.com',
            'phone' => '01812345678',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(20)->toDateString(),
        ]))->assertStatus(422)->assertJsonValidationErrors('date_of_birth');
    }

    public function test_one_account_per_phone_number(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        // Same number in a different format must be rejected.
        $this->postJson('/api/v1/auth/register', $this->payload([
            'email' => 'other@example.com',
            'phone' => '+8801712345678',
        ]))->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_a_user_can_log_in_with_email_or_phone(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/auth/login', ['login' => 'ayesha@example.com', 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/login', ['login' => '01712345678', 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/login', ['login' => 'ayesha@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }
}
