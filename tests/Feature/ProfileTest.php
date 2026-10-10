<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_profile_with_explicit_full_name_en(): void
    {
        $user = User::factory()->male()->create(['name' => 'Faisal Bodda']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'full_name_en' => 'Faisal Ahmed Bodda',
            'about' => 'Looking for a compatible partner.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.full_name_en', 'Faisal Ahmed Bodda')
            ->assertJsonPath('data.about', 'Looking for a compatible partner.');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'full_name_en' => 'Faisal Ahmed Bodda',
            'gender' => 'male',
        ]);
    }

    public function test_user_can_create_profile_falling_back_to_registered_user_name(): void
    {
        $user = User::factory()->male()->create(['name' => 'Faisal Bodda']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'about' => 'Only about provided in step 5.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.full_name_en', 'Faisal Bodda')
            ->assertJsonPath('data.about', 'Only about provided in step 5.');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'full_name_en' => 'Faisal Bodda',
        ]);
    }

    public function test_user_cannot_create_profile_twice(): void
    {
        $user = User::factory()->male()->create(['name' => 'Faisal Bodda']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', ['about' => 'First creation'])->assertCreated();

        $secondResponse = $this->postJson('/api/v1/profile', ['about' => 'Second creation']);
        $secondResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['profile']);
    }

    public function test_validation_error_is_properly_returned_when_name_is_missing(): void
    {
        $user = User::factory()->male()->create(['name' => '']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'about' => 'No name anywhere.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name_en'])
            ->assertJsonPath('errors.full_name_en.0', 'Full name (English) is required to create a profile.');
    }
}
