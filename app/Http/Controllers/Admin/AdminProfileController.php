<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Profile;
use App\Models\Religion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function index(Request $request): View
    {
        $query = Profile::with(['user', 'district', 'religion', 'profession', 'photos'])->latest();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                    ->orWhere('full_name_en', 'like', "%{$search}%")
                    ->orWhere('full_name_bn', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%");
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('religion_id')) {
            $query->where('religion_id', $request->input('religion_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }

        if ($request->filled('verification_level')) {
            $query->where('verification_level', $request->input('verification_level'));
        }

        $profiles = $query->paginate(15)->withQueryString();
        $religions = Religion::all();
        $districts = Location::where('type', 'district')->orderBy('name_en')->get();

        return view('admin.profiles.index', compact('profiles', 'religions', 'districts'));
    }

    public function show(Profile $profile): View
    {
        $profile->load([
            'user',
            'photos',
            'district',
            'upazila',
            'religion',
            'profession',
            'educationLevel',
            'religionAttributes',
            'educations',
            'partnerPreference.preferenceWeights',
            'privacySettings',
            'contactBlocks',
            'managers.user',
        ]);

        return view('admin.profiles.show', compact('profile'));
    }

    public function toggleStatus(Profile $profile): RedirectResponse
    {
        $user = $profile->user;
        if (! $user) {
            return back()->with('error', 'Profile has no associated user account.');
        }

        $newStatus = $user->status === AccountStatus::Active ? AccountStatus::Hidden : AccountStatus::Active;
        $user->update(['status' => $newStatus]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'profile.status_toggled',
            subject: $profile,
            context: ['new_status' => $newStatus->value]
        );

        return back()->with('success', "Profile status updated to {$newStatus->value}.");
    }
}
