@extends('admin.layouts.admin')

@section('title', 'Biodata: ' . $profile->full_name_en)
@section('page_title', 'Candidate Biodata — ' . $profile->public_id)

@section('content')
<div class="space-y-6">
    <!-- Header Summary Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-2xl bg-rose-50 border-2 border-rose-200 flex items-center justify-center text-rose-700 font-extrabold text-3xl shadow-xs shrink-0">
                {{ strtoupper(substr($profile->full_name_en, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black text-slate-900">{{ $profile->full_name_en }}</h2>
                    @if($profile->full_name_bn)
                        <span class="text-slate-500 font-serif text-lg">({{ $profile->full_name_bn }})</span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800">
                        Level {{ $profile->verification_level }} Verified
                    </span>
                </div>
                <div class="mt-1 font-mono text-sm text-rose-600 font-bold">
                    Public ID: {{ $profile->public_id }}
                </div>
                <p class="mt-1 text-sm text-slate-600 italic">"{{ $profile->headline ?? 'No headline provided' }}"</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($profile->user)
                <a href="{{ route('admin.users.show', $profile->user) }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-xs">
                    Manage Owning User Account
                </a>
            @endif
            <form action="{{ route('admin.profiles.toggle-status', $profile) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs rounded-xl">
                    {{ $profile->user?->status === \App\Enums\AccountStatus::Active ? 'Hide from Discovery' : 'Activate Profile' }}
                </button>
            </form>
        </div>
    </div>

    <!-- 2-Column Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Basic, Education, Career, Family (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- About Me -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-2">About Candidate</h3>
                <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">{{ $profile->about ?? 'No about details entered.' }}</p>
            </div>

            <!-- Personal & Location -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">Personal & Location</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Gender</div>
                        <div class="font-medium text-slate-800 capitalize">{{ $profile->gender?->value }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Age</div>
                        <div class="font-medium text-slate-800">{{ $profile->age ?? '—' }} years</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Height</div>
                        <div class="font-medium text-slate-800">{{ $profile->height_cm ? $profile->height_cm . ' cm' : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Marital Status</div>
                        <div class="font-medium text-slate-800 capitalize">{{ $profile->marital_status ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">District & Upazila</div>
                        <div class="font-medium text-slate-800">{{ $profile->district?->name_en ?? '—' }}, {{ $profile->upazila?->name_en ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Nationality</div>
                        <div class="font-medium text-slate-800">{{ $profile->nationality ?? 'Bangladeshi' }}</div>
                    </div>
                </div>
            </div>

            <!-- Career & Education -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">Career & Education</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Profession</div>
                        <div class="font-medium text-slate-800">{{ $profile->profession?->name_en ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Job Title</div>
                        <div class="font-medium text-slate-800">{{ $profile->job_title ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Organization</div>
                        <div class="font-medium text-slate-800">{{ $profile->organization ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Income (BDT)</div>
                        <div class="font-medium text-slate-800">{{ $profile->income_min ? number_format($profile->income_min) . ' - ' . number_format($profile->income_max) : '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Work Location</div>
                        <div class="font-medium text-slate-800">{{ $profile->work_location ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Highest Education</div>
                        <div class="font-medium text-slate-800">{{ $profile->educationLevel?->name_en ?? '—' }}</div>
                    </div>
                </div>

                @if($profile->educations->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="text-xs text-slate-400 uppercase font-semibold mb-2">Education History</div>
                        <div class="space-y-2">
                            @foreach($profile->educations as $edu)
                                <div class="text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                    <span class="font-bold text-slate-800">{{ $edu->institution }}</span>
                                    @if($edu->degree) &bull; {{ $edu->degree }} @endif
                                    @if($edu->field_of_study) ({{ $edu->field_of_study }}) @endif
                                    @if($edu->graduation_year) &bull; Class of {{ $edu->graduation_year }} @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Family Details -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">Family Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Father's Occupation</div>
                        <div class="font-medium text-slate-800">{{ $profile->father_occupation ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Mother's Occupation</div>
                        <div class="font-medium text-slate-800">{{ $profile->mother_occupation ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Siblings</div>
                        <div class="font-medium text-slate-800">{{ $profile->siblings_count ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Family Type</div>
                        <div class="font-medium text-slate-800 capitalize">{{ $profile->family_type ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 uppercase font-semibold">Family Location</div>
                        <div class="font-medium text-slate-800">{{ $profile->family_location ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Religion Specific Attributes -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-4 border-b border-slate-100 pb-2">
                    Religion Details ({{ $profile->religion?->name_en ?? 'Not set' }})
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @forelse($profile->religionAttributes as $attr)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <span class="text-slate-400 uppercase font-semibold block">{{ str_replace('_', ' ', $attr->key) }}</span>
                            <span class="font-bold text-slate-800 text-sm mt-0.5 block">{{ $attr->value }}</span>
                        </div>
                    @empty
                        <div class="col-span-full text-slate-400 text-xs py-2">No religious attributes recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Photos, Managers, Preferences (1 col) -->
        <div class="space-y-6">
            <!-- Photos -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-900 text-base">Photos</h3>
                    <span class="text-xs font-bold text-slate-400">{{ $profile->photos->count() }} images</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @forelse($profile->photos as $photo)
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-100 flex flex-col justify-between">
                            <div class="aspect-square relative">
                                <img src="{{ route('admin.photos.preview', $photo) }}" alt="Candidate" class="w-full h-full object-cover">
                                @if($photo->is_primary)
                                    <span class="absolute top-1 left-1 bg-amber-500 text-slate-900 text-[9px] font-black px-1.5 py-0.5 rounded">PRIMARY</span>
                                @endif
                                <span class="absolute bottom-1 right-1 text-[8px] font-bold px-1 py-0.5 rounded {{ $photo->moderation_status === \App\Enums\PhotoModerationStatus::Approved ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">
                                    {{ strtoupper($photo->moderation_status->value) }}
                                </span>
                            </div>
                            <div class="p-1.5 flex gap-1 justify-center">
                                @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Approved)
                                    <form action="{{ route('admin.photos.approve', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded bg-emerald-600 text-white text-[10px] font-bold">Approve</button>
                                    </form>
                                @endif
                                @if($photo->moderation_status !== \App\Enums\PhotoModerationStatus::Rejected)
                                    <form action="{{ route('admin.photos.reject', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-bold">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-6 text-center text-slate-400 text-xs">No photos uploaded.</div>
                    @endforelse
                </div>
            </div>

            <!-- Profile Managers (Guardians §5) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-3">Profile Managers (§5)</h3>
                <div class="space-y-2">
                    @forelse($profile->managers as $mgr)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            <div class="font-bold text-slate-900">{{ $mgr->user?->name ?? 'User #' . $mgr->user_id }}</div>
                            <div class="text-slate-500 capitalize">Relationship: <strong>{{ $mgr->relationship->value }}</strong></div>
                            @if($mgr->permissions)
                                <div class="mt-1 text-[11px] text-slate-400">Permissions: {{ implode(', ', $mgr->permissions) }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-slate-400 text-xs">Only self-managed.</div>
                    @endforelse
                </div>
            </div>

            <!-- Relative Contact Blocklist (D2) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <h3 class="font-bold text-slate-900 text-base mb-1">Privacy From Relatives (D2)</h3>
                <p class="text-xs text-slate-500 mb-3">Blocked relative phone hashes</p>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl text-xs">
                    <span class="text-slate-600">Active Excluded Numbers:</span>
                    <span class="font-bold text-rose-600 text-sm">{{ $profile->contactBlocks->count() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
