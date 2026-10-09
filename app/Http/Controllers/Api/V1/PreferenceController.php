<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePreferenceRequest;
use App\Models\PartnerPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return response()->json(['message' => 'Create a profile first.'], 404);
        }

        $preference = $profile->partnerPreference()->with('weights')->first();

        return response()->json(['data' => $this->present($preference)]);
    }

    public function update(UpdatePreferenceRequest $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return response()->json(['message' => 'Create a profile first.'], 404);
        }

        $data = $request->validated();
        $weights = $data['weights'] ?? null;
        unset($data['weights']);

        $preference = DB::transaction(function () use ($profile, $data, $weights) {
            /** @var PartnerPreference $preference */
            $preference = $profile->partnerPreference()->updateOrCreate([], $data);

            if ($weights !== null) {
                $preference->weights()->delete();
                foreach ($weights as $w) {
                    $preference->weights()->create([
                        'preference_key' => $w['preference_key'],
                        'importance' => $w['importance'],
                    ]);
                }
            }

            return $preference->load('weights');
        });

        return response()->json(['data' => $this->present($preference)]);
    }

    private function present(?PartnerPreference $preference): ?array
    {
        if (! $preference) {
            return null;
        }

        return [
            'age_range' => [$preference->age_min, $preference->age_max],
            'height_range_cm' => [$preference->height_min_cm, $preference->height_max_cm],
            'marital_statuses' => $preference->marital_statuses,
            'religion_id' => $preference->religion_id,
            'min_education_level_id' => $preference->min_education_level_id,
            'profession_ids' => $preference->profession_ids,
            'income_min' => $preference->income_min,
            'district_ids' => $preference->district_ids,
            'other_expectations' => $preference->other_expectations,
            'weights' => $preference->weights->map(fn ($w) => [
                'preference_key' => $w->preference_key,
                'importance' => $w->importance->value,
            ]),
        ];
    }
}
