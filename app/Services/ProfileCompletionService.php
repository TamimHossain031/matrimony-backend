<?php

namespace App\Services;

use App\Models\Profile;

/**
 * Profile completion is computed by a service, never maintained by hand in
 * several places (blueprint §18).
 */
class ProfileCompletionService
{
    /**
     * Weighted completion sections. Each returns true when "done".
     *
     * @return array<string, array{weight:int, done:bool}>
     */
    protected function sections(Profile $profile): array
    {
        $lifestyle = $profile->lifestyle ?? [];

        return [
            'basics' => [
                'weight' => 20,
                'done' => filled($profile->full_name_en) && filled($profile->date_of_birth) && filled($profile->gender),
            ],
            'location' => [
                'weight' => 10,
                'done' => filled($profile->district_id),
            ],
            'physical' => [
                'weight' => 10,
                'done' => filled($profile->height_cm) && filled($profile->marital_status),
            ],
            'religion' => [
                'weight' => 10,
                'done' => filled($profile->religion_id),
            ],
            'education' => [
                'weight' => 15,
                'done' => filled($profile->education_level_id),
            ],
            'career' => [
                'weight' => 15,
                'done' => filled($profile->profession_id),
            ],
            'about' => [
                'weight' => 10,
                'done' => filled($profile->about) || filled($profile->headline),
            ],
            'lifestyle' => [
                'weight' => 5,
                'done' => ! empty($lifestyle),
            ],
            'photo' => [
                'weight' => 5,
                'done' => (bool) $profile->has_photo,
            ],
        ];
    }

    public function percent(Profile $profile): int
    {
        $earned = 0;
        $total = 0;
        foreach ($this->sections($profile) as $section) {
            $total += $section['weight'];
            if ($section['done']) {
                $earned += $section['weight'];
            }
        }

        return $total > 0 ? (int) round($earned / $total * 100) : 0;
    }

    /** Recompute and persist the cached completion_percent column. */
    public function sync(Profile $profile): int
    {
        $percent = $this->percent($profile);
        if ($profile->completion_percent !== $percent) {
            $profile->forceFill(['completion_percent' => $percent])->saveQuietly();
        }

        return $percent;
    }

    /** @return list<string> keys of sections still incomplete */
    public function missingSections(Profile $profile): array
    {
        return collect($this->sections($profile))
            ->reject(fn ($s) => $s['done'])
            ->keys()
            ->all();
    }
}
