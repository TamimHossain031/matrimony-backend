<?php

namespace Tests\Feature;

use App\Models\GreetingTemplate;
use Database\Seeders\GreetingTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreetingCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GreetingTemplateSeeder::class);
    }

    public function test_can_list_active_templates_and_filter_by_occasion(): void
    {
        $response = $this->getJson('/api/v1/greeting-templates');
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'occasion', 'description', 'thumbnail_path', 'design_config', 'sort_order'],
                ],
            ]);

        $weddingResponse = $this->getJson('/api/v1/greeting-templates?occasion=wedding');
        $weddingResponse->assertOk();
        $items = $weddingResponse->json('data');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertEquals('wedding', $item['occasion']);
        }
    }

    public function test_anonymous_user_can_create_and_publish_greeting_card_without_email(): void
    {
        $template = GreetingTemplate::where('occasion', 'wedding')->firstOrFail();

        $response = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => 'Ayesha & Farhan',
            'sender_name' => 'Tamim & Family',
            'title' => 'Happy Wedding!',
            'message' => 'May your marriage be blessed with unending love, mutual respect, and joy.',
            'theme_settings' => [
                'fontFamily' => 'serif',
                'particleType' => 'golden_stars',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'public_id',
                    'occasion',
                    'recipient_name',
                    'sender_name',
                    'title',
                    'message',
                    'theme_settings',
                    'status',
                    'template' => ['id', 'name', 'slug'],
                ],
                'edit_token',
                'public_id',
                'share_url',
            ]);

        $publicId = $response->json('public_id');
        $this->assertNotEmpty($publicId);
        $this->assertDatabaseHas('greeting_cards', [
            'public_id' => $publicId,
            'recipient_name' => 'Ayesha & Farhan',
            'status' => 'published',
        ]);
    }

    public function test_validation_errors_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/greeting-cards', [
            'recipient_name' => '',
            'message' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['template_id', 'recipient_name', 'message']);
    }

    public function test_inputs_are_sanitized_against_malicious_scripts(): void
    {
        $template = GreetingTemplate::firstOrFail();

        $response = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => '<script>alert("hacked")</script>Sarah',
            'sender_name' => '<b>John</b>',
            'title' => '<i>Celebration</i>',
            'message' => '<script>stealCookies()</script>Wishing you the best year ahead!',
        ]);

        $response->assertCreated();
        $this->assertEquals('Sarah', $response->json('data.recipient_name'));
        $this->assertEquals('John', $response->json('data.sender_name'));
        $this->assertEquals('Celebration', $response->json('data.title'));
        $this->assertStringNotContainsString('<script>', $response->json('data.message'));
    }

    public function test_can_view_published_greeting_card_publicly(): void
    {
        $template = GreetingTemplate::firstOrFail();

        $createResponse = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => 'Rahim',
            'message' => 'Happy Anniversary my dear friend!',
        ]);

        $publicId = $createResponse->json('public_id');

        $viewResponse = $this->getJson("/api/v1/public/greeting-cards/{$publicId}");
        $viewResponse->assertOk()
            ->assertJsonPath('data.public_id', $publicId)
            ->assertJsonPath('data.recipient_name', 'Rahim')
            ->assertJsonPath('data.views_count', 1);

        $this->assertDatabaseHas('greeting_cards', [
            'public_id' => $publicId,
            'views_count' => 1,
        ]);
        $this->assertDatabaseHas('greeting_card_events', [
            'event_type' => 'opened',
        ]);
    }

    public function test_returns_404_for_unknown_card(): void
    {
        $response = $this->getJson('/api/v1/public/greeting-cards/nonexistent99');
        $response->assertNotFound();
    }

    public function test_can_record_engagement_events(): void
    {
        $template = GreetingTemplate::firstOrFail();

        $createResponse = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => 'Nadia',
            'message' => 'Warm wishes for your special day!',
        ]);

        $publicId = $createResponse->json('public_id');

        $eventResponse = $this->postJson("/api/v1/public/greeting-cards/{$publicId}/events", [
            'event_type' => 'share_clicked',
        ]);

        $eventResponse->assertOk()
            ->assertJsonPath('shares_count', 1);

        $reactionResponse = $this->postJson("/api/v1/public/greeting-cards/{$publicId}/events", [
            'event_type' => 'reaction_clicked',
        ]);

        $reactionResponse->assertOk()
            ->assertJsonPath('reactions_count', 1);
    }

    public function test_can_report_inappropriate_card(): void
    {
        $template = GreetingTemplate::firstOrFail();

        $createResponse = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => 'Spam Test',
            'message' => 'Suspicious content',
        ]);

        $publicId = $createResponse->json('public_id');

        $reportResponse = $this->postJson("/api/v1/public/greeting-cards/{$publicId}/report", [
            'reason' => 'inappropriate_content',
            'details' => 'This card contains suspicious spam text.',
        ]);

        $reportResponse->assertCreated();
        $this->assertDatabaseHas('greeting_card_reports', [
            'reason' => 'inappropriate_content',
            'status' => 'pending',
        ]);
    }

    public function test_creator_with_edit_token_can_update_and_delete_card(): void
    {
        $template = GreetingTemplate::firstOrFail();

        $createResponse = $this->postJson('/api/v1/greeting-cards', [
            'template_id' => $template->id,
            'recipient_name' => 'Original Name',
            'message' => 'Original message',
        ]);

        $publicId = $createResponse->json('public_id');
        $editToken = $createResponse->json('edit_token');

        // Attempt without edit token -> forbidden
        $unauthUpdate = $this->patchJson("/api/v1/greeting-cards/{$publicId}", [
            'recipient_name' => 'Hacked Name',
        ]);
        $unauthUpdate->assertForbidden();

        // Update with valid edit token header
        $authUpdate = $this->withHeaders(['X-Edit-Token' => $editToken])
            ->patchJson("/api/v1/greeting-cards/{$publicId}", [
                'recipient_name' => 'Updated Name',
                'message' => 'Updated message',
            ]);

        $authUpdate->assertOk()
            ->assertJsonPath('data.recipient_name', 'Updated Name');

        // Delete with valid edit token header
        $authDelete = $this->withHeaders(['X-Edit-Token' => $editToken])
            ->deleteJson("/api/v1/greeting-cards/{$publicId}");

        $authDelete->assertOk();
        $this->assertDatabaseHas('greeting_cards', [
            'public_id' => $publicId,
            'status' => 'deleted',
        ]);
    }
}
