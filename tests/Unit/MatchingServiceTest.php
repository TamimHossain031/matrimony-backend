<?php

namespace Tests\Unit;

use App\Enums\Importance;
use App\Models\PartnerPreference;
use App\Models\PreferenceWeight;
use App\Models\Profile;
use App\Services\MatchingService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    private function preference(array $attrs, array $weights = []): PartnerPreference
    {
        $pref = new PartnerPreference($attrs);
        $pref->setRelation('weights', new Collection(array_map(
            fn ($w) => new PreferenceWeight($w),
            $weights,
        )));

        return $pref;
    }

    public function test_a_fully_matching_candidate_scores_100(): void
    {
        $pref = $this->preference([
            'age_min' => 24, 'age_max' => 30,
            'district_ids' => [5],
        ], [
            ['preference_key' => 'age_range', 'importance' => Importance::DealBreaker->value],
            ['preference_key' => 'district', 'importance' => Importance::NiceToHave->value],
        ]);

        $candidate = new Profile([
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'district_id' => 5,
        ]);

        $result = (new MatchingService)->evaluate($pref, $candidate);

        $this->assertSame(100, $result['score']);
        $this->assertSame(1, $result['must_haves_total']);
        $this->assertSame(1, $result['must_haves_met']);
        $this->assertCount(0, $result['missed']);
    }

    public function test_a_failed_deal_breaker_heavily_penalizes_the_score(): void
    {
        $pref = $this->preference([
            'age_min' => 24, 'age_max' => 30,
            'district_ids' => [5],
        ], [
            ['preference_key' => 'age_range', 'importance' => Importance::DealBreaker->value],
            ['preference_key' => 'district', 'importance' => Importance::NiceToHave->value],
        ]);

        // Right district, but age is outside the must-have range.
        $candidate = new Profile([
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'district_id' => 5,
        ]);

        $result = (new MatchingService)->evaluate($pref, $candidate);

        $this->assertLessThan(20, $result['score']);
        $this->assertSame(0, $result['must_haves_met']);
        $this->assertSame('age', $result['missed'][0]['label']);
    }

    public function test_a_missed_nice_to_have_only_reduces_the_score(): void
    {
        $pref = $this->preference([
            'age_min' => 24, 'age_max' => 30,
            'district_ids' => [5],
        ], [
            ['preference_key' => 'age_range', 'importance' => Importance::DealBreaker->value],
            ['preference_key' => 'district', 'importance' => Importance::NiceToHave->value],
        ]);

        // Age matches (deal-breaker met), wrong district (nice-to-have missed).
        $candidate = new Profile([
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'district_id' => 99,
        ]);

        $result = (new MatchingService)->evaluate($pref, $candidate);

        // deal-breaker weight 2 met of 3 total → 2/3 ≈ 67, no penalty.
        $this->assertGreaterThan(50, $result['score']);
        $this->assertLessThan(100, $result['score']);
        $this->assertSame(1, $result['must_haves_met']);
    }
}
