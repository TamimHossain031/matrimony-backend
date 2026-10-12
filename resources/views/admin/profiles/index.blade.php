@extends('admin.layouts.admin')

@section('title', 'Candidate Profiles')
@section('page_title', 'Matrimony Profiles Directory')

@section('content')
<div class="space-y-6">
    <!-- Filter Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.profiles.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Search Profile</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Public ID (BD-...), Name, Headline..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm">
            </div>

            <!-- Gender -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Gender</label>
                <select name="gender" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                    <option value="">All Genders</option>
                    @foreach(\App\Enums\Gender::cases() as $g)
                        <option value="{{ $g->value }}" {{ request('gender') === $g->value ? 'selected' : '' }}>{{ ucfirst($g->value) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Religion -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Religion</label>
                <select name="religion_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                    <option value="">All Religions</option>
                    @foreach($religions as $r)
                        <option value="{{ $r->id }}" {{ request('religion_id') == $r->id ? 'selected' : '' }}>{{ $r->name_en }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit -->
            <div>
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Profiles Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Candidate</th>
                        <th class="px-6 py-3.5">Public ID</th>
                        <th class="px-6 py-3.5">Age & Gender</th>
                        <th class="px-6 py-3.5">Location</th>
                        <th class="px-6 py-3.5">Profession & Education</th>
                        <th class="px-6 py-3.5">Verification</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($profiles as $profile)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-700 font-bold flex items-center justify-center text-sm shrink-0 border border-rose-200">
                                    {{ strtoupper(substr($profile->full_name_en, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 truncate">{{ $profile->full_name_en }}</div>
                                    <div class="text-xs text-slate-500 truncate">{{ $profile->headline ?? 'No headline' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-xs text-rose-600">
                                {{ $profile->public_id }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div><strong>{{ $profile->age ?? '—' }} yrs</strong></div>
                                <div class="text-slate-500 capitalize">{{ $profile->gender?->value }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div>{{ $profile->district?->name_en ?? 'Unknown' }}</div>
                                <div class="text-slate-400">{{ $profile->religion?->name_en ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div>{{ $profile->profession?->name_en ?? 'Not specified' }}</div>
                                <div class="text-slate-400">{{ $profile->educationLevel?->name_en ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $profile->verification_level > 0 ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-500' }}">
                                    Level {{ $profile->verification_level }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($profile->user?->status === \App\Enums\AccountStatus::Active)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                                @elseif($profile->user?->status === \App\Enums\AccountStatus::Suspended)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Suspended</span>
                                @elseif($profile->user?->status === \App\Enums\AccountStatus::Hidden)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Hidden</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">{{ $profile->user?->status?->value ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.profiles.show', $profile) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs">
                                        View Biodata
                                    </a>
                                    <form action="{{ route('admin.profiles.toggle-status', $profile) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs" title="Toggle active/hidden">
                                            {{ $profile->user?->status === \App\Enums\AccountStatus::Active ? 'Hide' : 'Unhide' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                No candidate profiles found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($profiles->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $profiles->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
