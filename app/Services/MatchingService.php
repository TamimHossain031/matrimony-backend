<?php

namespace App\Services;

use App\Enums\Importance;
use App\Models\EducationLevel;
use App\Models\PartnerPreference;
use App\Models\Profile;

/**
 * Transparent matching (differentiator D3, blueprint §6).
 *
 * Returns both a compatibility score and an explanation object, so the frontend
 * can render "Match 82% · 7 of your 9 must-haves · Missed: location, height"
 * instead of a mystery percentage. Matching rules live here, never in a
 * controller (§6 architecture decision).
 */
class MatchingService
{
    /**
     * @return array{
     *   score:int,
     *   must_haves_total:int,
     *   must_haves_met:int,
     *   met:list<array<string,mixed>>,
     *   missed:list<array<string,mixed>>
     * }
     */
    public function evaluate(PartnerPreference $preference, Profile $candidate): array
    {
        $weights = $preference->relationLoaded('weights')
            ? $preference->weights
            : $preference->weights()->get();

        $importanceOf = function (string $key) use ($weights): Importance {
            $w = $weights->firstWhere('preference_key', $key);

            return $w?->importance ?? Importance::NiceToHave;
        };

        $criteria = $this->criteria($preference, $candidate);

        $met = [];
        $missed = [];
        $mustHavesTotal = 0;
        $mustHavesMet = 0;
        $weightSum = 0.0;
        $weightEarned = 0.0;
        $failedDealBreakers = 0;

        foreach ($criteria as $key => $result) {
            if ($result === null) {
                continue; // preference not set for this key → not evaluated
            }

            $importance = $importanceOf($key);
            $weight = $importance === Importance::DealBreaker ? 2.0 : 1.0;
            $weightSum += $weight;

            $row = [
                'key' => $key,
                'label' => $result['label'],
                'importance' => $importance->value,
                'preferred' => $result['preferred'] ?? null,
                'candidate_value' => $result['candidate'] ?? null,
            ];

            if ($importance === Importance::DealBreaker) {
                $mustHavesTotal++;
            }

            if ($result['met']) {
                $weightEarned += $weight;
                $met[] = $row;
                if ($importance === Importance::DealBreaker) {
                    $mustHavesMet++;
                }
            } else {
                $missed[] = $row;
                if ($importance === Importance::DealBreaker) {
                    $failedDealBreakers++;
                }
            }
        }

        $raw = $weightSum > 0 ? $weightEarned / $weightSum : 1.0;

        // Each failed deal-breaker heavily penalizes the score (§6).
        $penalty = (float) config('matrimony.matching.deal_breaker_penalty', 0.15);
        for ($i = 0; $i < $failedDealBreakers; $i++) {
            $raw *= $penalty;
        }

        return [
            'score' => (int) round($raw * 100),
            'must_haves_total' => $mustHavesTotal,
            'must_haves_met' => $mustHavesMet,
            'met' => $met,
            'missed' => $missed,
        ];
    }

    /**
     * Evaluate each criterion. Returns null when the preference isn't set.
     *
     * @return array<string, array{met:bool, label:string, preferred?:string, candidate?:string}|null>
     */
    protected function criteria(PartnerPreference $pref, Profile $c): array
    {
        return [
            'age_range' => $this->ageRange($pref, $c),
            'height_range' => $this->heightRange($pref, $c),
            'religion' => $this->religion($pref, $c),
            'education' => $this->education($pref, $c),
            'profession' => $this->profession($pref, $c),
            'income' => $this->income($pref, $c),
            'district' => $this->district($pref, $c),
            'marital_status' => $this->maritalStatus($pref, $c),
        ];
    }

    protected function ageRange(PartnerPreference $pref, Profile $c): ?array
    {
        if ($pref->age_min === null && $pref->age_max === null) {
            return null;
        }
        $age = $c->age;
        $met = $age !== null
            && ($pref->age_min === null || $age >= $pref->age_min)
            && ($pref->age_max === null || $age <= $pref->age_max);

        return [
            'met' => $met,
            'label' => 'age',
            'preferred' => trim(($pref->age_min ?? '').'–'.($pref->age_max ?? '')),
            'candidate' => (string) $age,
        ];
    }

    protected function heightRange(PartnerPreference $pref, Profile $c): ?array
    {
        if ($pref->height_min_cm === null && $pref->height_max_cm === null) {
            return null;
        }
        $h = $c->height_cm;
        $met = $h !== null
            && ($pref->height_min_cm === null || $h >= $pref->height_min_cm)
            && ($pref->height_max_cm === null || $h <= $pref->height_max_cm);

        return [
            'met' => $met,
            'label' => 'height',
            'preferred' => trim(($pref->height_min_cm ?? '').'–'.($pref->height_max_cm ?? '').' cm'),
            'candidate' => $h ? $h.' cm' : null,
        ];
    }

    protected function religion(PartnerPreference $pref, Profile $c): ?array
    {
        if ($pref->religion_id === null) {
            return null;
        }

        return [
            'met' => $c->religion_id === $pref->religion_id,
            'label' => 'religion',
        ];
    }

    protected function education(PartnerPreference $pref, Profile $c): ?array
    {
        if ($pref->min_education_level_id === null) {
            return null;
        }
        // Compare by the lookup `rank` so "at least bachelor" works.
        $minRank = optional($pref->minEducationLevel ?? EducationLevel::find($pref->min_education_level_id))->rank;
        $candRank = optional($c->educationLevel ?? EducationLevel::find($c->education_level_id))->rank;
        $met = $minRank !== null && $candRank !== null && $candRank >= $minRank;

        return [
            'met' => $met,
            'label' => 'education',
        ];
    }

    protected function profession(PartnerPreference $pref, Profile $c): ?array
    {
        $ids = $pref->profession_ids ?? [];
        if (empty($ids)) {
            return null;
        }

        return [
            'met' => in_array($c->profession_id, $ids, false),
            'label' => 'profession',
        ];
    }

    protected function income(PartnerPreference $pref, Profile $c): ?array
    {
        if ($pref->income_min === null) {
            return null;
        }
        // Candidate's upper income bound should reach the preferred minimum.
        $candidateIncome = $c->income_max ?? $c->income_min;
        $met = $candidateIncome !== null && $candidateIncome >= $pref->income_min;

        return [
            'met' => $met,
            'label' => 'income',
        ];
    }

    protected function district(PartnerPreference $pref, Profile $c): ?array
    {
        $ids = $pref->district_ids ?? [];
        if (empty($ids)) {
            return null;
        }

        return [
            'met' => in_array($c->district_id, $ids, false),
            'label' => 'location',
        ];
    }

    protected function maritalStatus(PartnerPreference $pref, Profile $c): ?array
    {
        $statuses = $pref->marital_statuses ?? [];
        if (empty($statuses)) {
            return null;
        }

        return [
            'met' => in_array($c->marital_status, $statuses, true),
            'label' => 'marital status',
        ];
    }
}
