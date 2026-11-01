<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the filtered discovery query (blueprint §7) — the query to watch (§25).
 *
 * Every discovery query applies the same exclusions, in both directions:
 *   - the viewer's own profile
 *   - same gender (matrimony is opposite-gender by default)
 *   - non-active accounts (hidden / deactivated / deleted)
 *   - blocked either way
 *   - already rejected / disconnected between the pair
 *   - the relative blocklist (D2), silently, in both directions
 */
class DiscoveryService
{
    /**
     * @param  array<string,mixed>  $filters
     */
    public function query(User $viewer, array $filters = []): Builder
    {
        $viewerProfile = $viewer->profile;
        $viewerProfileId = $viewerProfile?->id;
        $viewerPhoneHash = $viewer->phone_e164 ? Phone::hash($viewer->phone_e164) : null;

        $query = Profile::query()
            ->discoverable()                      // active accounts only
            ->where('user_id', '!=', $viewer->id); // not myself

        $this->applyExclusions($query, $viewer, $viewerProfileId, $viewerPhoneHash);
        $this->applyOppositeGender($query, $viewer, $filters);
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters['sort'] ?? 'recent');

        return $query;
    }

    protected function applyExclusions(Builder $query, User $viewer, ?int $viewerProfileId, ?string $viewerPhoneHash): void
    {
        // Blocked either direction.
        $query->whereNotExists(function ($sub) use ($viewer) {
            $sub->selectRaw('1')->from('blocks')
                ->where(function ($w) use ($viewer) {
                    $w->where('blocker_id', $viewer->id)->whereColumn('blocked_id', 'profiles.user_id');
                })
                ->orWhere(function ($w) use ($viewer) {
                    $w->where('blocked_id', $viewer->id)->whereColumn('blocker_id', 'profiles.user_id');
                });
        });

        // Already rejected or disconnected between the pair (either direction).
        $query->whereNotExists(function ($sub) use ($viewer) {
            $sub->selectRaw('1')->from('interests')
                ->whereIn('status', ['rejected', 'disconnected'])
                ->where(function ($w) use ($viewer) {
                    $w->where(function ($x) use ($viewer) {
                        $x->where('sender_id', $viewer->id)->whereColumn('receiver_id', 'profiles.user_id');
                    })->orWhere(function ($x) use ($viewer) {
                        $x->where('receiver_id', $viewer->id)->whereColumn('sender_id', 'profiles.user_id');
                    });
                });
        });

        // Relative blocklist (D2), both directions, silently.
        if ($viewerProfileId !== null) {
            // Direction A: the candidate's own number is in the viewer's blocklist.
            $query->whereNotExists(function ($sub) use ($viewerProfileId) {
                $sub->selectRaw('1')
                    ->from('profile_contact_blocks as vb')
                    ->join('users as cu', 'cu.phone_hash', '=', 'vb.phone_hash')
                    ->where('vb.profile_id', $viewerProfileId)
                    ->whereColumn('cu.id', 'profiles.user_id');
            });
        }

        if ($viewerPhoneHash !== null) {
            // Direction B: the viewer's number is in the candidate's blocklist.
            $query->whereNotExists(function ($sub) use ($viewerPhoneHash) {
                $sub->selectRaw('1')
                    ->from('profile_contact_blocks as cb')
                    ->whereColumn('cb.profile_id', 'profiles.id')
                    ->where('cb.phone_hash', $viewerPhoneHash);
            });
        }
    }

    protected function applyOppositeGender(Builder $query, User $viewer, array $filters): void
    {
        // Respect an explicit gender filter if the search allows it; otherwise
        // default to the opposite of the viewer's gender.
        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);

            return;
        }

        $viewerGender = $viewer->gender;
        if ($viewerGender instanceof Gender) {
            $opposite = $viewerGender === Gender::Male ? Gender::Female : Gender::Male;
            $query->where('gender', $opposite->value);
        }
    }

    /**
     * @param  array<string,mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Age range → date_of_birth window (age is computed, never stored).
        if (! empty($filters['age_min']) || ! empty($filters['age_max'])) {
            $today = now();
            if (! empty($filters['age_max'])) {
                $earliestDob = $today->copy()->subYears((int) $filters['age_max'] + 1)->addDay();
                $query->whereDate('date_of_birth', '>=', $earliestDob);
            }
            if (! empty($filters['age_min'])) {
                $latestDob = $today->copy()->subYears((int) $filters['age_min']);
                $query->whereDate('date_of_birth', '<=', $latestDob);
            }
        }

        foreach ([
            'district_id' => 'district_id',
            'religion_id' => 'religion_id',
            'profession_id' => 'profession_id',
            'education_level_id' => 'education_level_id',
            'marital_status' => 'marital_status',
        ] as $input => $column) {
            if (! empty($filters[$input])) {
                $query->where($column, $filters[$input]);
            }
        }

        if (! empty($filters['height_min'])) {
            $query->where('height_cm', '>=', (int) $filters['height_min']);
        }
        if (! empty($filters['height_max'])) {
            $query->where('height_cm', '<=', (int) $filters['height_max']);
        }
        if (! empty($filters['income_min'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('income_max', '>=', (int) $filters['income_min'])
                    ->orWhere('income_min', '>=', (int) $filters['income_min']);
            });
        }
        if (! empty($filters['min_verification_level'])) {
            $query->where('verification_level', '>=', (int) $filters['min_verification_level']);
        }
        if (! empty($filters['has_photo'])) {
            $query->where('has_photo', true);
        }
        if (! empty($filters['active_within_days'])) {
            $cutoff = now()->subDays((int) $filters['active_within_days']);
            $query->whereHas('user', fn ($q) => $q->where('last_active_at', '>=', $cutoff));
        }

        $this->applyRadius($query, $filters);
    }

    /**
     * "Near me": keep only profiles within `radius_km` of (lat, lng).
     *
     * A bounding-box pre-filter uses the (latitude, longitude) index to narrow
     * the set, then ST_Distance_Sphere does the exact great-circle check in
     * meters. Profiles without coordinates are never matched.
     *
     * @param  array<string,mixed>  $filters
     */
    protected function applyRadius(Builder $query, array $filters): void
    {
        if (empty($filters['lat']) || empty($filters['lng']) || empty($filters['radius_km'])) {
            return;
        }

        $lat = (float) $filters['lat'];
        $lng = (float) $filters['lng'];
        $meters = (float) $filters['radius_km'] * 1000;

        $latDelta = $meters / 111_320;
        $lngDelta = $meters / (111_320 * max(cos(deg2rad($lat)), 0.01));

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);

        // Exact great-circle check. MySQL has a native helper; other drivers
        // (SQLite in tests) use the spherical law of cosines with math functions.
        if ($query->getConnection()->getDriverName() === 'mysql') {
            $query->whereRaw(
                'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) <= ?',
                [$lng, $lat, $meters],
            );
        } else {
            $query->whereRaw(
                '6371000 * acos(min(1.0, '
                .'cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) '
                .'+ sin(radians(?)) * sin(radians(latitude)))) <= ?',
                [$lat, $lng, $lat, $meters],
            );
        }
    }

    protected function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->orderByDesc('profiles.created_at'),
            'verification' => $query->orderByDesc('verification_level')->orderByDesc('profiles.id'),
            // 'relevant' is applied in PHP after scoring; fall back to recent order here.
            default => $query->orderByDesc('profiles.id'),
        };
    }
}
