<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    /** The authenticated user's own profile. */
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return response()->json(['message' => 'No profile yet. Create one first.'], 404);
        }

        $profile->load($this->detailRelations());

        return response()->json(['data' => new ProfileResource($profile)]);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        if ($request->user()->profile()->exists()) {
            throw ValidationException::withMessages(['profile' => ['You already have a profile.']]);
        }

        $data = $request->validated();
        $attributes = $data['religion_attributes'] ?? [];
        unset($data['religion_attributes']);

        $profile = $this->profiles->createForUser($request->user(), $data);
        $this->syncReligionAttributes($profile, $attributes);

        $profile->load($this->detailRelations());

        return response()->json(['data' => new ProfileResource($profile)], 201);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return response()->json(['message' => 'No profile yet. Create one first.'], 404);
        }

        $this->authorize('update', $profile);

        $data = $request->validated();
        $attributes = $data['religion_attributes'] ?? null;
        unset($data['religion_attributes']);

        $profile = $this->profiles->update($profile, $data);
        if ($attributes !== null) {
            $this->syncReligionAttributes($profile, $attributes);
        }

        $profile->load($this->detailRelations());

        return response()->json(['data' => new ProfileResource($profile)]);
    }

    /** Account lifecycle transitions (blueprint §4). */
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                AccountStatus::Active->value,
                AccountStatus::Hidden->value,
                AccountStatus::Deactivated->value,
            ])],
        ]);

        $user = $request->user();
        $user->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Account status updated.',
            'status' => $user->status->value,
        ]);
    }

    /** Graceful close — soft delete with a retention window (§4). */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;
        if ($profile) {
            $this->authorize('delete', $profile);
            $profile->delete(); // soft delete
        }

        $user->update(['status' => AccountStatus::Deleted]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Profile closed.']);
    }

    /**
     * @param  array<string,string>  $attributes
     */
    private function syncReligionAttributes(Profile $profile, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '') {
                $profile->religionAttributes()->where('key', $key)->delete();

                continue;
            }
            $profile->religionAttributes()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /** @return list<string> */
    private function detailRelations(): array
    {
        return [
            'district', 'upazila', 'religion', 'profession', 'educationLevel',
            'educations', 'religionAttributes', 'managers', 'photos',
            'privacySettings', 'user',
        ];
    }
}
