<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVerificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Verification::with(['user.profile', 'reviewer'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        $verifications = $query->paginate(20);

        return response()->json($verifications);
    }

    public function show(Verification $verification): JsonResponse
    {
        $verification->load(['user.profile', 'reviewer']);

        return response()->json(['data' => $verification]);
    }

    public function updateStatus(Request $request, Verification $verification): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $validated['status'];
        $targetLevel = is_object($verification->level) ? $verification->level->value : (int) $verification->level;

        DB::transaction(function () use ($verification, $status, $targetLevel, $validated, $request) {
            $verification->update([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ]);

            if ($status === 'approved' && $verification->user?->profile) {
                $verification->user->profile->update([
                    'verification_level' => max($verification->user->profile->verification_level, $targetLevel),
                ]);
            }

            AuditLog::record(
                actor: $request->user(),
                action: "verification.{$status}",
                subject: $verification,
                context: [
                    'user_id' => $verification->user_id,
                    'level' => $targetLevel,
                    'review_note' => $validated['review_note'] ?? null,
                ]
            );
        });

        return response()->json([
            'message' => "Verification {$status} successfully.",
            'data' => $verification->fresh(['user.profile', 'reviewer']),
        ]);
    }
}
