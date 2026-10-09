<?php

namespace Tests\Feature;

use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Services\ContactBlockService;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoreLoopTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithProfile(string $gender, array $profile = []): array
    {
        $user = User::factory()->{$gender}()->create();
        $p = app(ProfileService::class)->createForUser($user, array_merge([
            'full_name_en' => ucfirst($gender).' User',
        ], $profile));

        return [$user, $p];
    }

    public function test_discovery_excludes_self_and_same_gender(): void
    {
        [$woman] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');
        $this->makeUserWithProfile('female'); // another woman — same gender as viewer

        Sanctum::actingAs($woman);

        $response = $this->getJson('/api/v1/profiles')->assertOk();
        $publicIds = collect($response->json('data'))->pluck('public_id');

        // Only the man appears: not herself, not the same-gender woman.
        $this->assertTrue($publicIds->contains($manProfile->public_id));
        $this->assertCount(1, $publicIds);
    }

    public function test_discovery_includes_a_match_breakdown_when_preferences_are_set(): void
    {
        [$woman, $womanProfile] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male', [
            'date_of_birth' => now()->subYears(30)->toDateString(),
        ]);

        // The woman wants a 28–34 year old as a deal-breaker.
        $womanProfile->partnerPreference()->create(['age_min' => 28, 'age_max' => 34])
            ->weights()->create(['preference_key' => 'age_range', 'importance' => 'deal_breaker']);

        Sanctum::actingAs($woman);
        $card = collect($this->getJson('/api/v1/profiles')->json('data'))
            ->firstWhere('public_id', $manProfile->public_id);

        $this->assertNotNull($card['match']);
        $this->assertSame(100, $card['match']['score']);
        $this->assertSame(1, $card['match']['must_haves_met']);
    }

    public function test_interest_flow_accept_opens_a_conversation(): void
    {
        [$woman] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        Sanctum::actingAs($woman);
        $this->postJson('/api/v1/interests', ['profile_id' => $manProfile->public_id])
            ->assertCreated();

        $interest = Interest::first();
        $this->assertEquals('pending', $interest->status->value);

        // The man accepts.
        Sanctum::actingAs($man);
        $this->patchJson("/api/v1/interests/{$interest->id}", ['action' => 'accept'])
            ->assertOk()->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('conversations', ['is_closed' => false]);
    }

    public function test_simultaneous_interest_auto_accepts(): void
    {
        [$woman, $womanProfile] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        // Woman → Man
        Sanctum::actingAs($woman);
        $this->postJson('/api/v1/interests', ['profile_id' => $manProfile->public_id])->assertCreated();

        // Man → Woman (before responding) should auto-accept into a connection.
        Sanctum::actingAs($man);
        $this->postJson('/api/v1/interests', ['profile_id' => $womanProfile->public_id])->assertCreated();

        $this->assertDatabaseHas('interests', [
            'sender_id' => $woman->id, 'receiver_id' => $man->id, 'status' => 'accepted',
        ]);
        $this->assertDatabaseHas('conversations', ['is_closed' => false]);
    }

    public function test_messaging_requires_a_connection_and_masks_contact_info(): void
    {
        [$woman] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        Sanctum::actingAs($woman);
        $this->postJson('/api/v1/interests', ['profile_id' => $manProfile->public_id])->assertCreated();
        $interest = Interest::first();

        Sanctum::actingAs($man);
        $this->patchJson("/api/v1/interests/{$interest->id}", ['action' => 'accept'])->assertOk();

        $conversation = \App\Models\Conversation::first();

        // A message with a phone number gets masked before it persists.
        Sanctum::actingAs($woman);
        $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
            'body' => 'Call me on 01712345678 please',
        ])->assertCreated()->assertJsonPath('data.was_masked', true);

        $this->assertStringNotContainsString('01712345678', \App\Models\Message::first()->body);
    }

    public function test_relative_blocklist_excludes_a_profile_both_ways(): void
    {
        [$woman, $womanProfile] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        // The woman blocks the man's phone number (D2).
        app(ContactBlockService::class)->add($womanProfile, $man->phone);

        // The man must not see the woman, and the woman must not see the man —
        // silently, in both directions.
        Sanctum::actingAs($man);
        $menView = collect($this->getJson('/api/v1/profiles')->json('data'))->pluck('public_id');
        $this->assertFalse($menView->contains($womanProfile->public_id));

        Sanctum::actingAs($woman);
        $womanView = collect($this->getJson('/api/v1/profiles')->json('data'))->pluck('public_id');
        $this->assertFalse($womanView->contains($manProfile->public_id));
    }

    public function test_hidden_profile_returns_404_everywhere(): void
    {
        [$woman] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        // The man hides his account.
        $man->update(['status' => \App\Enums\AccountStatus::Hidden]);

        Sanctum::actingAs($woman);
        $this->getJson("/api/v1/profiles/{$manProfile->public_id}")->assertNotFound();
    }

    public function test_a_user_cannot_edit_another_users_profile(): void
    {
        [$woman] = $this->makeUserWithProfile('female');
        [$man, $manProfile] = $this->makeUserWithProfile('male');

        // The man has his own profile; the woman editing /profile only ever
        // touches her own, so the man's headline is untouched.
        Sanctum::actingAs($woman);
        $this->patchJson('/api/v1/profile', ['headline' => 'Hacked'])->assertOk();

        $this->assertNotEquals('Hacked', $manProfile->fresh()->headline);
    }
}
