<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('profile')->latest();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'profile.photos',
            'profile.religion',
            'profile.district',
            'managedProfiles.profile',
            'entitlement',
            'verifications' => fn ($q) => $q->latest(),
        ]);

        return response()->json(['data' => $user]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot modify your own status.'], 422);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(AccountStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $user->status;
        $user->update(['status' => $validated['status']]);

        AuditLog::record(
            actor: $request->user(),
            action: 'user.status_updated',
            subject: $user,
            context: [
                'from_status' => $previousStatus->value,
                'to_status' => $validated['status'],
                'reason' => $request->input('reason'),
            ]
        );

        return response()->json([
            'message' => "User status updated to {$validated['status']}.",
            'data' => $user,
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Only administrators can update roles.'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot modify your own role.'], 422);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $previousRole = $user->role;
        $user->update(['role' => $validated['role']]);

        AuditLog::record(
            actor: $request->user(),
            action: 'user.role_changed',
            subject: $user,
            context: [
                'from_role' => $previousRole->value,
                'to_role' => $validated['role'],
            ]
        );

        return response()->json([
            'message' => "User role updated to {$validated['role']}.",
            'data' => $user,
        ]);
    }

    public function updateVerificationLevel(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'verification_level' => ['required', 'integer', 'between:0,4'],
        ]);

        if (! $user->profile) {
            return response()->json(['message' => 'User does not have a profile.'], 422);
        }

        $previousLevel = $user->profile->verification_level;
        $user->profile->update(['verification_level' => $validated['verification_level']]);

        AuditLog::record(
            actor: $request->user(),
            action: 'user.verification_level_updated',
            subject: $user,
            context: [
                'from_level' => $previousLevel,
                'to_level' => (int) $validated['verification_level'],
            ]
        );

        return response()->json([
            'message' => "Verification level updated to {$validated['verification_level']}.",
            'data' => $user->load('profile'),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 422);
        }

        $user->update(['status' => AccountStatus::Deleted]);
        $user->delete();

        if ($user->profile) {
            $user->profile->delete();
        }

        AuditLog::record(
            actor: $request->user(),
            action: 'user.deleted',
            subject: $user
        );

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
