<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\GreetingCard;
use App\Models\GreetingTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminGreetingTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@shondhan.test',
        ]);

        Storage::fake('public');
    }

    public function test_admin_can_view_greeting_templates_index(): void
    {
        GreetingTemplate::create([
            'name' => 'Golden Vows',
            'slug' => 'golden-vows',
            'occasion' => 'wedding',
            'description' => 'A royal gold theme',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.greeting-templates.index'));

        $response->assertStatus(200);
        $response->assertSee('Golden Vows');
        $response->assertSee('Designs & Templates');
    }

    public function test_admin_can_upload_greeting_card_image_when_creating_template(): void
    {
        $image = UploadedFile::fake()->image('wedding_art.jpg', 800, 600);

        $response = $this->actingAs($this->admin)->post(route('admin.greeting-templates.store'), [
            'name' => 'Royal Emerald Nikah',
            'slug' => 'royal-emerald-nikah',
            'occasion' => 'wedding',
            'description' => 'Islamic arabesque with emerald accents.',
            'image' => $image,
            'badge' => '💍 Holy Nikah',
            'primaryColor' => '#183860',
            'accentColor' => '#dfb15b',
            'cardBg' => '#f7faff',
            'textColor' => '#112239',
            'borderStyle' => 'arabesque',
            'fontFamily' => 'cinzel',
            'particleType' => 'golden_stars',
            'is_active' => '1',
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.greeting-templates.index'));
        $response->assertSessionHas('success');

        $template = GreetingTemplate::where('slug', 'royal-emerald-nikah')->first();
        $this->assertNotNull($template);
        $this->assertNotNull($template->thumbnail_path);
        Storage::disk('public')->assertExists($template->thumbnail_path);

        $this->assertEquals('wedding', $template->occasion);
        $this->assertEquals('#183860', $template->design_config['primaryColor']);
        $this->assertTrue($template->is_active);

        // Check AuditLog was recorded
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->admin->id,
            'action' => 'greeting_template.created',
        ]);
    }

    public function test_admin_can_update_template_and_replace_greeting_card_image(): void
    {
        $oldFile = UploadedFile::fake()->image('old_art.jpg');
        $oldPath = $oldFile->store('greeting-templates', 'public');

        $template = GreetingTemplate::create([
            'name' => 'Vintage Rose',
            'slug' => 'vintage-rose',
            'occasion' => 'wedding',
            'thumbnail_path' => $oldPath,
            'design_config' => ['primaryColor' => '#9c4d62'],
            'is_active' => true,
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $newImage = UploadedFile::fake()->image('new_floral_art.png', 1000, 800);

        $response = $this->actingAs($this->admin)->put(route('admin.greeting-templates.update', $template), [
            'name' => 'Vintage Rose Elegance',
            'slug' => 'vintage-rose-elegance',
            'occasion' => 'wedding',
            'description' => 'Updated romantic floral motif.',
            'image' => $newImage,
            'primaryColor' => '#b83b5e',
            'accentColor' => '#f08a5d',
            'cardBg' => '#fffdfd',
            'textColor' => '#2e0214',
            'borderStyle' => 'floral',
            'fontFamily' => 'script',
            'particleType' => 'rose_petals',
            'is_active' => '1',
            'sort_order' => 2,
        ]);

        $response->assertRedirect(route('admin.greeting-templates.index'));

        // Old file deleted and new file stored
        Storage::disk('public')->assertMissing($oldPath);

        $template->refresh();
        $this->assertNotEquals($oldPath, $template->thumbnail_path);
        Storage::disk('public')->assertExists($template->thumbnail_path);
        $this->assertEquals('Vintage Rose Elegance', $template->name);
    }

    public function test_admin_can_remove_uploaded_image_from_template(): void
    {
        $file = UploadedFile::fake()->image('card.jpg');
        $path = $file->store('greeting-templates', 'public');

        $template = GreetingTemplate::create([
            'name' => 'Minimal Pearl',
            'slug' => 'minimal-pearl',
            'occasion' => 'anniversary',
            'thumbnail_path' => $path,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.greeting-templates.update', $template), [
            'name' => 'Minimal Pearl',
            'slug' => 'minimal-pearl',
            'occasion' => 'anniversary',
            'remove_image' => '1',
            'primaryColor' => '#5c6b73',
            'accentColor' => '#c0c8cc',
            'cardBg' => '#f8f9fa',
            'textColor' => '#1d2328',
            'borderStyle' => 'solid',
            'fontFamily' => 'sans',
            'particleType' => 'none',
        ]);

        $response->assertRedirect(route('admin.greeting-templates.index'));

        Storage::disk('public')->assertMissing($path);
        $template->refresh();
        $this->assertNull($template->thumbnail_path);
    }

    public function test_admin_can_toggle_template_active_status(): void
    {
        $template = GreetingTemplate::create([
            'name' => 'Champagne Cheers',
            'slug' => 'champagne-cheers',
            'occasion' => 'birthday',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.greeting-templates.toggle-active', $template));
        $response->assertRedirect();

        $template->refresh();
        $this->assertFalse($template->is_active);

        // Toggle back
        $this->actingAs($this->admin)->post(route('admin.greeting-templates.toggle-active', $template));
        $template->refresh();
        $this->assertTrue($template->is_active);
    }

    public function test_admin_can_delete_template(): void
    {
        $file = UploadedFile::fake()->image('deleteme.jpg');
        $path = $file->store('greeting-templates', 'public');

        $template = GreetingTemplate::create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'occasion' => 'thankyou',
            'thumbnail_path' => $path,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.greeting-templates.destroy', $template));
        $response->assertRedirect(route('admin.greeting-templates.index'));

        $this->assertDatabaseMissing('greeting_templates', ['id' => $template->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_public_templates_api_returns_thumbnail_url(): void
    {
        $file = UploadedFile::fake()->image('api_test.jpg');
        $path = $file->store('greeting-templates', 'public');

        $template = GreetingTemplate::create([
            'name' => 'Starlight Dream',
            'slug' => 'starlight-dream',
            'occasion' => 'love',
            'thumbnail_path' => $path,
            'design_config' => ['primaryColor' => '#a14258'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/greeting-templates');

        $response->assertStatus(200);
        $json = $response->json('data');
        $match = collect($json)->firstWhere('slug', 'starlight-dream');

        $this->assertNotNull($match);
        $this->assertNotNull($match['thumbnail_url']);
        $this->assertStringContainsString('storage/greeting-templates', $match['thumbnail_url']);
    }

    public function test_admin_can_view_sent_cards_and_change_status(): void
    {
        $template = GreetingTemplate::create([
            'name' => 'Card Test Template',
            'slug' => 'card-test-tpl',
            'occasion' => 'wedding',
            'is_active' => true,
        ]);

        $card = GreetingCard::create([
            'public_id' => 'gc_test12345678',
            'edit_token' => 'token123',
            'template_id' => $template->id,
            'occasion' => 'wedding',
            'recipient_name' => 'Ayesha Rahman',
            'sender_name' => 'Tamim',
            'title' => 'Happy Wedding!',
            'message' => 'Heartiest congratulations to you both!',
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.greeting-cards.index'));
        $response->assertStatus(200);
        $response->assertSee('Ayesha Rahman');
        $response->assertSee('gc_test12345678');

        // Admin disables card
        $postRes = $this->actingAs($this->admin)->post(route('admin.greeting-cards.status', $card), [
            'status' => 'disabled',
        ]);
        $postRes->assertRedirect();

        $card->refresh();
        $this->assertEquals('disabled', $card->status);
    }
}
