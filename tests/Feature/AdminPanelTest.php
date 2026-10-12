<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\PhotoModerationStatus;
use App\Enums\ReportReason;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_renders_successfully(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200)
            ->assertSee('Admin Portal')
            ->assertSee('Sign in to Admin');
    }

    public function test_admin_can_login_with_valid_staff_credentials(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/admin/login', [
            'login' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_staff_user_cannot_login_to_admin_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => UserRole::User,
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/admin/login', [
            'login' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_login_to_admin_or_api(): void
    {
        $suspendedAdmin = User::factory()->admin()->suspended()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->post('/admin/login', [
            'login' => 'suspended@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();

        // API login test for suspended status
        $apiResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'suspended@example.com',
            'password' => 'secret123',
        ]);

        $apiResponse->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    public function test_unauthenticated_user_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_dashboard_with_platform_statistics(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200)
            ->assertSee('Platform Overview')
            ->assertSee('Candidate Profiles')
            ->assertSee('Pending Reports');
    }

    public function test_admin_can_list_and_search_users(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create([
            'name' => 'Target User Name',
            'email' => 'target@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/users?search=Target');

        $response->assertStatus(200)
            ->assertSee('Target User Name')
            ->assertSee('target@example.com');
    }

    public function test_admin_can_view_user_details(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_admin_can_suspend_and_restore_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['status' => AccountStatus::Active]);

        // Suspend
        $suspendResponse = $this->actingAs($admin)->post("/admin/users/{$user->id}/suspend", [
            'reason' => 'Violation of community safety terms',
        ]);

        $suspendResponse->assertSessionHas('success');
        $this->assertEquals(AccountStatus::Suspended, $user->fresh()->status);

        // Check AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.suspended',
            'subject_id' => $user->id,
        ]);

        // Restore
        $restoreResponse = $this->actingAs($admin)->post("/admin/users/{$user->id}/restore");

        $restoreResponse->assertSessionHas('success');
        $this->assertEquals(AccountStatus::Active, $user->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.restored',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/suspend");

        $response->assertSessionHas('error');
        $this->assertEquals(AccountStatus::Active, $admin->fresh()->status);
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::User]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/role", [
            'role' => UserRole::Moderator->value,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(UserRole::Moderator, $user->fresh()->role);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.role_changed',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_update_user_verification_level(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'verification_level' => 0,
        ]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/verification-level", [
            'verification_level' => 3,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(3, $profile->fresh()->verification_level);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.verification_level_updated',
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}");

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertEquals(AccountStatus::Deleted, $user->fresh()->status);
    }

    public function test_admin_can_list_and_view_candidate_profiles(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $profile = Profile::factory()->create([
            'user_id' => $user->id,
            'full_name_en' => 'Farhana Kabir',
            'public_id' => 'BD-TEST-001',
        ]);

        $listResponse = $this->actingAs($admin)->get('/admin/profiles');
        $listResponse->assertStatus(200)
            ->assertSee('Farhana Kabir')
            ->assertSee('BD-TEST-001');

        $showResponse = $this->actingAs($admin)->get("/admin/profiles/{$profile->id}");
        $showResponse->assertStatus(200)
            ->assertSee('Farhana Kabir')
            ->assertSee('BD-TEST-001');
    }

    public function test_admin_can_toggle_profile_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['status' => AccountStatus::Active]);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin)->post("/admin/profiles/{$profile->id}/toggle-status");

        $response->assertSessionHas('success');
        $this->assertEquals(AccountStatus::Hidden, $user->fresh()->status);
    }

    public function test_admin_can_review_and_resolve_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $reported = User::factory()->create();

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => ReportReason::Harassment,
            'details' => 'Offensive messages sent in chat',
            'status' => 'open',
        ]);

        $listResponse = $this->actingAs($admin)->get('/admin/reports');
        $listResponse->assertStatus(200)->assertSee('harassment');

        $showResponse = $this->actingAs($admin)->get("/admin/reports/{$report->id}");
        $showResponse->assertStatus(200)->assertSee('Offensive messages sent in chat');

        $updateResponse = $this->actingAs($admin)->post("/admin/reports/{$report->id}/status", [
            'status' => 'resolved',
            'resolution_note' => 'Warned user regarding harassment policy',
        ]);

        $updateResponse->assertSessionHas('success');
        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertEquals('Warned user regarding harassment policy', $report->fresh()->resolution_note);
        $this->assertEquals($admin->id, $report->fresh()->handled_by);
    }

    public function test_admin_can_suspend_reported_user_and_resolve_report_together(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->create();
        $reported = User::factory()->create(['status' => AccountStatus::Active]);

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => ReportReason::Scam,
            'details' => 'Soliciting fraudulent money transfers',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->post("/admin/reports/{$report->id}/action-suspend", [
            'resolution_note' => 'Confirmed scammer. Suspended permanently.',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(AccountStatus::Suspended, $reported->fresh()->status);
        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertEquals('Confirmed scammer. Suspended permanently.', $report->fresh()->resolution_note);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.suspended_via_report',
            'subject_id' => $reported->id,
        ]);
    }

    public function test_user_can_submit_verification_and_admin_can_approve(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'verification_level' => 0]);
        $admin = User::factory()->admin()->create();

        // User submits verification claim via API
        $token = $user->createToken('test')->plainTextToken;
        $submitResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/verifications', [
                'level' => 2,
            ]);

        $submitResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $verification = Verification::first();
        $this->assertNotNull($verification);

        // Admin reviews and approves
        $approveResponse = $this->actingAs($admin)->post("/admin/verifications/{$verification->id}/approve", [
            'review_note' => 'NID card matches user identity',
        ]);

        $approveResponse->assertSessionHas('success');
        $this->assertEquals('approved', $verification->fresh()->status);
        $this->assertEquals(2, $profile->fresh()->verification_level);
        $this->assertEquals($admin->id, $verification->fresh()->reviewed_by);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'verification.approved',
            'subject_id' => $verification->id,
        ]);
    }

    public function test_admin_can_reject_verification_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $verification = Verification::create([
            'user_id' => $user->id,
            'level' => 3,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/verifications/{$verification->id}/reject", [
            'review_note' => 'Selfie was blurry and could not be verified against NID',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('rejected', $verification->fresh()->status);
        $this->assertEquals('Selfie was blurry and could not be verified against NID', $verification->fresh()->review_note);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'verification.rejected',
            'subject_id' => $verification->id,
        ]);
    }

    public function test_admin_can_moderate_photos_and_batch_approve(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'has_photo' => false]);

        $photo1 = ProfilePhoto::create([
            'profile_id' => $profile->id,
            'path' => 'profile-photos/test1.jpg',
            'is_primary' => true,
            'moderation_status' => PhotoModerationStatus::Pending,
        ]);

        $photo2 = ProfilePhoto::create([
            'profile_id' => $profile->id,
            'path' => 'profile-photos/test2.jpg',
            'is_primary' => false,
            'moderation_status' => PhotoModerationStatus::Pending,
        ]);

        // View photos index
        $listResponse = $this->actingAs($admin)->get('/admin/photos');
        $listResponse->assertStatus(200);

        // Batch approve
        $batchResponse = $this->actingAs($admin)->post('/admin/photos/batch', [
            'action' => 'approve',
            'photo_ids' => [$photo1->id, $photo2->id],
        ]);

        $batchResponse->assertSessionHas('success');
        $this->assertEquals(PhotoModerationStatus::Approved, $photo1->fresh()->moderation_status);
        $this->assertEquals(PhotoModerationStatus::Approved, $photo2->fresh()->moderation_status);
        $this->assertTrue($profile->fresh()->has_photo);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'photo.batch_approve',
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::record(
            actor: $admin,
            action: 'test.action',
            context: ['detail' => 'testing audit viewer']
        );

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertStatus(200)
            ->assertSee('test.action')
            ->assertSee($admin->name);
    }

    public function test_admin_api_endpoints_work_with_bearer_token(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $token = $admin->createToken('admin-api')->plainTextToken;

        // Dashboard
        $dashResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/dashboard');

        $dashResponse->assertStatus(200)
            ->assertJsonStructure(['stats' => ['total_users', 'active_users', 'pending_reports']]);

        // Users list
        $usersResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $usersResponse->assertStatus(200)
            ->assertJsonStructure(['data']);

        // Update user status via API
        $statusResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/users/{$user->id}/status", [
                'status' => 'suspended',
                'reason' => 'Violated terms via API moderation',
            ]);

        $statusResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertEquals(AccountStatus::Suspended, $user->fresh()->status);
    }
}
