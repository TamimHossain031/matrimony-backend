@extends('admin.layouts.admin')

@section('title', 'Manage ' . $user->name)
@section('page_title', 'User Profile & Moderation: ' . $user->name)

@section('content')
<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white font-extrabold text-2xl flex items-center justify-center shadow-lg">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $user->role === \App\Enums\UserRole::Admin ? 'bg-purple-100 text-purple-800' : ($user->role === \App\Enums\UserRole::Moderator ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                        {{ $user->role->value }}
                    </span>
                    @if($user->status === \App\Enums\AccountStatus::Active)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Active</span>
                    @elseif($user->status === \App\Enums\AccountStatus::Suspended)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Suspended</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 capitalize">{{ $user->status->value }}</span>
                    @endif
                </div>
                <div class="mt-1 text-sm text-slate-500 flex flex-wrap gap-4">
                    <span>Email: <strong>{{ $user->email }}</strong></span>
                    <span>Phone: <strong>{{ $user->phone }}</strong></span>
                    <span>Gender: <strong class="capitalize">{{ $user->gender?->value ?? 'Not specified' }}</strong></span>
                    <span>Age: <strong>{{ $user->age() ?? 'N/A' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($user->status === \App\Enums\AccountStatus::Suspended)
                <form action="{{ route('admin.users.restore', $user) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs">
                        Restore User to Active
                    </button>
                </form>
            @elseif($user->id !== auth()->id())
                <form action="{{ route('admin.users.suspend', $user) }}" method="POST" onsubmit="return confirm('Suspend this user account?');">
                    @csrf
                    <input type="hidden" name="reason" value="Manual staff suspension">
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-xs">
                        Suspend Account
                    </button>
                </form>
            @endif

            @if($user->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this account permanently/soft?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 font-semibold text-xs rounded-xl">
                        Delete Account
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Quick Role & Verification Action Forms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Verification Ladder Update -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="font-bold text-slate-900 text-sm mb-3">Verification Ladder Level (§15)</h3>
            <form action="{{ route('admin.users.verification-level', $user) }}" method="POST" class="flex items-center gap-3">
                @csrf
                <select name="verification_level" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                    @foreach(\App\Enums\VerificationLevel::cases() as $lvl)
                        <option value="{{ $lvl->value }}" {{ ($user->profile?->verification_level ?? 0) === $lvl->value ? 'selected' : '' }}>
                            Level {{ $lvl->value }}: {{ $lvl->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shrink-0">
                    Update Level
                </button>
            </form>
            <p class="text-xs text-slate-400 mt-2">Currently: <strong>Level {{ $user->profile?->verification_level ?? 0 }}</strong></p>
        </div>

        <!-- Role Update (Admin Only) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <h3 class="font-bold text-slate-900 text-sm mb-3">Assign Account Role</h3>
            @if(auth()->user()->isAdmin())
                <form action="{{ route('admin.users.role', $user) }}" method="POST" class="flex items-center gap-3">
                    @csrf
                    <select name="role" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                        @foreach(\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>
                                {{ ucfirst($role->value) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" {{ $user->id === auth()->id() ? 'disabled' : '' }} class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shrink-0 disabled:opacity-50">
                        Change Role
                    </button>
                </form>
                <p class="text-xs text-slate-400 mt-2">Staff accounts have access to this portal.</p>
            @else
                <p class="text-xs text-slate-500">Only administrators can modify user roles.</p>
            @endif
        </div>
    </div>

    <!-- Main Profile Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900">Candidate Matrimony Profile</h3>
                <p class="text-xs text-slate-500">Public ID: <span class="font-mono font-bold text-rose-600">{{ $user->profile?->public_id ?? 'None' }}</span></p>
            </div>
            @if($user->profile)
                <a href="{{ route('admin.profiles.show', $user->profile) }}" class="text-xs font-semibold text-rose-600 hover:underline">
                    View Full Biodata &rarr;
                </a>
            @endif
        </div>

        @if($user->profile)
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <h4 class="font-bold text-slate-800 mb-2">Basic Info</h4>
                    <ul class="space-y-1.5 text-slate-600">
                        <li><strong>Headline:</strong> {{ $user->profile->headline ?? 'Not set' }}</li>
                        <li><strong>Name (EN):</strong> {{ $user->profile->full_name_en }}</li>
                        <li><strong>Name (BN):</strong> {{ $user->profile->full_name_bn ?? '—' }}</li>
                        <li><strong>Marital Status:</strong> {{ $user->profile->marital_status ?? '—' }}</li>
                        <li><strong>Height:</strong> {{ $user->profile->height_cm ? $user->profile->height_cm . ' cm' : '—' }}</li>
                        <li><strong>Religion:</strong> {{ $user->profile->religion?->name_en ?? '—' }}</li>
                        <li><strong>District:</strong> {{ $user->profile->district?->name_en ?? '—' }}</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-800 mb-2">Career & Education</h4>
                    <ul class="space-y-1.5 text-slate-600">
                        <li><strong>Profession:</strong> {{ $user->profile->profession?->name_en ?? '—' }}</li>
                        <li><strong>Job Title:</strong> {{ $user->profile->job_title ?? '—' }}</li>
                        <li><strong>Organization:</strong> {{ $user->profile->organization ?? '—' }}</li>
                        <li><strong>Education Level:</strong> {{ $user->profile->educationLevel?->name_en ?? '—' }}</li>
                        <li><strong>Institution:</strong> {{ $user->profile->institution ?? '—' }}</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-800 mb-2">Entitlement & Safety</h4>
                    <ul class="space-y-1.5 text-slate-600">
                        <li><strong>Plan:</strong> <span class="uppercase font-semibold text-slate-800">{{ $user->entitlement?->plan ?? 'free' }}</span></li>
                        <li><strong>Daily Interest Limit:</strong> {{ $user->entitlement?->daily_interest_limit ?? 10 }}</li>
                        <li><strong>Created by:</strong> <span class="capitalize">{{ $user->profile_created_by->value }}</span></li>
                        <li><strong>Has Photo:</strong> {{ $user->profile->has_photo ? 'Yes' : 'No' }}</li>
                    </ul>
                </div>
            </div>

            <!-- Profile Photos -->
            <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                <h4 class="font-bold text-slate-800 text-sm mb-4">Photos Gallery ({{ $user->profile->photos->count() }})</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @forelse($user->profile->photos as $photo)
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs flex flex-col justify-between">
                            <div class="aspect-square bg-slate-200 relative">
                                <img src="{{ route('admin.photos.preview', $photo) }}" alt="Photo" class="w-full h-full object-cover">
                                @if($photo->is_primary)
                                    <span class="absolute top-1 left-1 bg-amber-500 text-slate-900 text-[9px] font-black px-1.5 py-0.5 rounded">PRIMARY</span>
                                @endif
                                <span class="absolute bottom-1 right-1 text-[9px] font-bold px-1.5 py-0.5 rounded {{ $photo->moderation_status === \App\Enums\PhotoModerationStatus::Approved ? 'bg-emerald-600 text-white' : ($photo->moderation_status === \App\Enums\PhotoModerationStatus::Pending ? 'bg-amber-500 text-white' : 'bg-rose-600 text-white') }}">
                                    {{ strtoupper($photo->moderation_status->value) }}
                                </span>
                            </div>
                            <div class="p-2 flex gap-1 justify-center">
                                @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Approved)
                                    <form action="{{ route('admin.photos.approve', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded bg-emerald-600 text-white text-[10px] font-bold">Approve</button>
                                    </form>
                                @endif
                                @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Rejected)
                                    <form action="{{ route('admin.photos.reject', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded bg-rose-600 text-white text-[10px] font-bold">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-4 text-center text-slate-400 text-xs">
                            No photos uploaded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="p-8 text-center text-slate-400 text-sm">
                This user has registered an account but has not yet created a candidate matrimony profile.
            </div>
        @endif
    </div>

    <!-- Reports History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Reports Filed Against This User ({{ $user->reportsAgainst->count() }})</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($user->reportsAgainst as $report)
                <div class="p-4 flex items-center justify-between gap-4 text-sm">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                {{ str_replace('_', ' ', $report->reason->value) }}
                            </span>
                            <span class="text-xs text-slate-400">Status: <strong class="capitalize">{{ $report->status }}</strong></span>
                            <span class="text-xs text-slate-400">{{ $report->created_at->format('M d, Y') }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1">Details: {{ $report->details ?? 'None provided' }}</p>
                    </div>
                    <a href="{{ route('admin.reports.show', $report) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold">
                        View Report
                    </a>
                </div>
            @empty
                <div class="p-6 text-center text-slate-400 text-xs">
                    No safety reports on record for this user.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
