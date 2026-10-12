<?php

namespace Tests\Feature;

use App\Enums\PhotoModerationStatus;
use App\Enums\PhotoRequestStatus;
use App\Models\PhotoRequest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoBlurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function profileFor(User $user): Profile
    {
        return Profile::factory()->forUser($user)->create();
    }

    private function uploadAs(User $owner): ProfilePhoto
    {
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/photos', ['photo' => UploadedFile::fake()->image('me.jpg', 600, 600)])->assertCreated();

        return ProfilePhoto::firstOrFail();
    }

    public function test_upload_generates_a_blurred_copy_next_to_the_original(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);

        $photo = $this->uploadAs($owner);

        $this->assertNotNull($photo->blurred_path);
        $this->assertNotSame($photo->path, $photo->blurred_path);
        Storage::disk('local')->assertExists([$photo->path, $photo->blurred_path]);
    }

    public function test_viewer_without_access_only_receives_the_blurred_url(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $photo = $this->uploadAs($owner);
        $photo->update(['moderation_status' => PhotoModerationStatus::Approved, 'visibility' => 'accepted_connection']);

        Sanctum::actingAs(User::factory()->create());
        $profile = $photo->profile;

        $response = $this->getJson("/api/v1/profiles/{$profile->public_id}");
        $photoJson = $response->json('data.photos.0');

        $this->assertTrue($photoJson['blurred']);
        $this->assertNull($photoJson['url']);
        $this->assertNotNull($photoJson['blurred_url']);

        $this->get($photoJson['blurred_url'])->assertOk();
    }

    public function test_blurred_route_requires_a_valid_signature(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $photo = $this->uploadAs($owner);

        $this->getJson("/api/v1/photos/{$photo->id}/blurred")->assertForbidden();
    }

    public function test_approved_photo_request_unlocks_the_original(): void
    {
        $owner = User::factory()->create();
        $profile = $this->profileFor($owner);
        $photo = $this->uploadAs($owner);
        $photo->update(['moderation_status' => PhotoModerationStatus::Approved, 'visibility' => 'private']);

        $viewer = User::factory()->create();
        PhotoRequest::create(['profile_id' => $profile->id, 'requester_id' => $viewer->id, 'status' => PhotoRequestStatus::Approved]);
        Sanctum::actingAs($viewer);

        $photoJson = $this->getJson("/api/v1/profiles/{$profile->public_id}")->json('data.photos.0');

        $this->assertFalse($photoJson['blurred']);
        $this->assertNotNull($photoJson['url']);
    }

    public function test_owner_sees_their_own_pending_photo_unblurred(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $this->uploadAs($owner);

        $photoJson = $this->getJson('/api/v1/photos')->json('data.0');

        $this->assertFalse($photoJson['blurred']);
        $this->assertNotNull($photoJson['url']);
    }

    public function test_deleting_a_photo_removes_the_blurred_copy_too(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $photo = $this->uploadAs($owner);

        $this->deleteJson("/api/v1/photos/{$photo->id}")->assertOk();

        Storage::disk('local')->assertMissing([$photo->path, $photo->blurred_path]);
    }

    public function test_owner_can_update_photo_visibility(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $photo = $this->uploadAs($owner);

        Sanctum::actingAs($owner);
        $res = $this->patchJson("/api/v1/photos/{$photo->id}", [
            'visibility' => 'private',
        ])->assertOk();

        $this->assertEquals('private', $res->json('data.visibility'));
        $this->assertEquals('private', $photo->fresh()->visibility->value);
    }

    public function test_auth_me_includes_avatar_url(): void
    {
        $owner = User::factory()->create();
        $this->profileFor($owner);
        $this->uploadAs($owner);

        Sanctum::actingAs($owner);
        $res = $this->getJson('/api/v1/auth/me')->assertOk();

        $this->assertNotNull($res->json('data.avatar_url'));
    }
}
