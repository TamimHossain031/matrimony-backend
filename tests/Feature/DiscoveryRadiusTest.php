<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscoveryRadiusTest extends TestCase
{
    use RefreshDatabase;

    // A reference point in Dhaka; candidates are placed relative to it.
    private const CENTER_LAT = 23.7500;

    private const CENTER_LNG = 90.4000;

    /** The viewer searching from the center point. */
    private function actingViewer(): User
    {
        $viewer = User::factory()->male()->create();
        Profile::factory()->forUser($viewer)->create();
        Sanctum::actingAs($viewer);

        return $viewer;
    }

    /** A discoverable (female) candidate at the given offset from center. */
    private function candidateAt(string $publicId, ?float $latOffset, ?float $lngOffset): Profile
    {
        $user = User::factory()->female()->create();

        $coords = $latOffset === null
            ? ['latitude' => null, 'longitude' => null]
            : ['latitude' => self::CENTER_LAT + $latOffset, 'longitude' => self::CENTER_LNG + ($lngOffset ?? 0)];

        return Profile::factory()->forUser($user)->create(array_merge(
            ['public_id' => $publicId],
            $coords,
        ));
    }

    public function test_radius_returns_only_profiles_inside_it(): void
    {
        $this->actingViewer();

        $this->candidateAt('NEAR', 0.004, 0);   // ~0.45 km
        $this->candidateAt('MID', 0.027, 0);    // ~3.0 km
        $this->candidateAt('FAR', 0.090, 0);    // ~10 km

        $response = $this->getJson('/api/v1/profiles?'.http_build_query([
            'lat' => self::CENTER_LAT,
            'lng' => self::CENTER_LNG,
            'radius_km' => 1,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('public_id')->all();

        $this->assertContains('NEAR', $ids);
        $this->assertNotContains('MID', $ids);
        $this->assertNotContains('FAR', $ids);
    }

    public function test_larger_radius_includes_more_profiles(): void
    {
        $this->actingViewer();

        $this->candidateAt('NEAR', 0.004, 0);   // ~0.45 km
        $this->candidateAt('MID', 0.027, 0);    // ~3.0 km
        $this->candidateAt('FAR', 0.090, 0);    // ~10 km

        $response = $this->getJson('/api/v1/profiles?'.http_build_query([
            'lat' => self::CENTER_LAT,
            'lng' => self::CENTER_LNG,
            'radius_km' => 5,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('public_id')->all();

        $this->assertContains('NEAR', $ids);
        $this->assertContains('MID', $ids);
        $this->assertNotContains('FAR', $ids);
    }

    public function test_profiles_without_coordinates_are_excluded_from_radius_search(): void
    {
        $this->actingViewer();

        $this->candidateAt('NEAR', 0.004, 0);
        $this->candidateAt('NOCOORDS', null, null);

        $response = $this->getJson('/api/v1/profiles?'.http_build_query([
            'lat' => self::CENTER_LAT,
            'lng' => self::CENTER_LNG,
            'radius_km' => 5,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('public_id')->all();

        $this->assertContains('NEAR', $ids);
        $this->assertNotContains('NOCOORDS', $ids);
    }

    public function test_without_radius_params_all_discoverable_profiles_return(): void
    {
        $this->actingViewer();

        $this->candidateAt('NEAR', 0.004, 0);
        $this->candidateAt('FAR', 0.090, 0);
        $this->candidateAt('NOCOORDS', null, null);

        $response = $this->getJson('/api/v1/profiles');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('public_id')->all();

        $this->assertContains('NEAR', $ids);
        $this->assertContains('FAR', $ids);
        $this->assertContains('NOCOORDS', $ids);
    }

    public function test_radius_requires_lat_and_lng(): void
    {
        $this->actingViewer();

        $this->getJson('/api/v1/profiles?radius_km=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }
}
