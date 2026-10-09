<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\Shortlist;
use App\Models\User;
use Database\Seeders\BangladeshUserSeeder;
use Database\Seeders\LookupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BangladeshUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(LookupSeeder::class);
    }

    public function test_it_seeds_bangladeshi_profiles_with_complete_attributes(): void
    {
        $this->seed(BangladeshUserSeeder::class);

        // At least 16 candidate profiles seeded
        $this->assertGreaterThanOrEqual(16, Profile::count());

        $femaleCount = Profile::where('gender', Gender::Female)->count();
        $maleCount = Profile::where('gender', Gender::Male)->count();

        $this->assertGreaterThanOrEqual(8, $femaleCount);
        $this->assertGreaterThanOrEqual(8, $maleCount);

        // All profiles should have high completion percent (100%) and valid public IDs
        Profile::all()->each(function (Profile $profile) {
            $this->assertStringStartsWith('BD-', $profile->public_id);
            $this->assertSame(100, $profile->completion_percent);
            $this->assertTrue($profile->has_photo);
            $this->assertNotNull($profile->district_id);
            $this->assertNotNull($profile->religion_id);
            $this->assertNotNull($profile->profession_id);
            $this->assertNotNull($profile->education_level_id);

            // Related models check
            $this->assertNotEmpty($profile->photos);
            $this->assertNotNull($profile->privacySettings);
            $this->assertNotNull($profile->partnerPreference);
            $this->assertNotEmpty($profile->educations);
            $this->assertNotEmpty($profile->managers);
        });
    }

    public function test_it_seeds_interactive_features_when_tamim_user_exists(): void
    {
        $tamim = User::factory()->create([
            'email' => 'tamimhossain031@gmail.com',
            'phone' => '01609604791',
        ]);

        $this->seed(BangladeshUserSeeder::class);

        $tamim->refresh();
        $this->assertNotNull($tamim->profile);
        $this->assertSame(100, $tamim->profile->completion_percent);

        // Tamim should have shortlists, interests, notifications and conversations
        $this->assertGreaterThanOrEqual(1, Shortlist::where('user_id', $tamim->id)->count());
        $this->assertGreaterThanOrEqual(1, Interest::where('sender_id', $tamim->id)->count());
        $this->assertGreaterThanOrEqual(1, Interest::where('receiver_id', $tamim->id)->count());
        $this->assertGreaterThanOrEqual(1, Conversation::where('user_one_id', $tamim->id)->orWhere('user_two_id', $tamim->id)->count());
        $this->assertGreaterThanOrEqual(1, $tamim->notifications()->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(BangladeshUserSeeder::class);
        $countInitial = Profile::count();

        // Running a second time should not duplicate records or fail
        $this->seed(BangladeshUserSeeder::class);
        $this->assertSame($countInitial, Profile::count());
    }
}
