<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_id' => ['required', 'string', 'exists:profiles,public_id'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = Profile::where('public_id', $validated['profile_id'])->firstOrFail();

        abort_if($profile->user_id === $request->user()->id, 422, 'You cannot report yourself.');

        Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $profile->user_id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
        ]);

        return response()->json(['message' => 'Report submitted. Our team will review it.'], 201);
    }
}
