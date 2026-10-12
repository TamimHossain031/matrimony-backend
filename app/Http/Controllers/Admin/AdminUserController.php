<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('profile')->latest();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($pq) use ($search) {
                        $pq->where('public_id', 'like', "%{$search}%")
                            ->orWhere('full_name_en', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load([
            'profile.photos',
            'profile.religion',
            'profile.district',
            'profile.profession',
            'profile.educationLevel',
            'managedProfiles.profile',
            'entitlement',
            'verifications' => fn ($q) => $q->with('reviewer')->latest(),
            'reportsAgainst' => fn ($q) => $q->with('reporter')->latest(),
            'reportsSubmitted' => fn ($q) => $q->with('reportedUser')->latest(),
        ]);

        $auditLogs = AuditLog::with('actor')
            ->where(function ($q) use ($user) {
                $q->where('subject_type', User::class)
                    ->where('subject_id', $user->id);
            })
            ->latest('id')
            ->take(10)
            ->get();

        return view('admin.users.show', compact('user', 'auditLogs'));
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend your own account.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $previousStatus = $user->status;
        $user->update(['status' => AccountStatus::Suspended]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'user.suspended',
            subject: $user,
            context: [
                'previous_status' => $previousStatus->value,
                'reason' => $request->input('reason', 'Suspended by admin moderation'),
            ]
        );

        return back()->with('success', "User '{$user->name}' has been suspended.");
    }

    public function restore(User $user): RedirectResponse
    {
        $previousStatus = $user->status;
        $user->update(['status' => AccountStatus::Active]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'user.restored',
            subject: $user,
            context: ['previous_status' => $previousStatus?->value ?? 'suspended']
        );

        return back()->with('success', "User '{$user->name}' has been restored to active status.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            return back()->with('error', 'Only full administrators can alter user roles.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $previousRole = $user->role;
        $user->update(['role' => $validated['role']]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'user.role_changed',
            subject: $user,
            context: [
                'from_role' => $previousRole->value,
                'to_role' => $validated['role'],
            ]
        );

        return back()->with('success', "User '{$user->name}' role changed to {$validated['role']}.");
    }

    public function updateVerificationLevel(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'verification_level' => ['required', 'integer', 'between:0,4'],
        ]);

        if (! $user->profile) {
            return back()->with('error', 'User has no profile to update verification level for.');
        }

        $previousLevel = $user->profile->verification_level;
        $user->profile->update(['verification_level' => $validated['verification_level']]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'user.verification_level_updated',
            subject: $user,
            context: [
                'from_level' => $previousLevel,
                'to_level' => (int) $validated['verification_level'],
            ]
        );

        return back()->with('success', "Verification level updated to Level {$validated['verification_level']}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->update(['status' => AccountStatus::Deleted]);
        $user->delete();

        if ($user->profile) {
            $user->profile->delete();
        }

        AuditLog::record(
            actor: auth()->user(),
            action: 'user.deleted',
            subject: $user,
            context: ['deleted_at' => now()->toIso8601String()]
        );

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' has been soft deleted.");
    }
}
