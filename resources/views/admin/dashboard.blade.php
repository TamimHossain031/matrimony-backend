@extends('admin.layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Platform Overview & Moderation Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Top KPI Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Users -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Users</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($stats['total_users']) }}</span>
                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $stats['active_users'] }} active</span>
            </div>
            <div class="mt-3 text-xs text-slate-500">+{{ $stats['new_users_30d'] }} new in last 30 days</div>
        </div>

        <!-- Candidate Profiles -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Candidate Profiles</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($stats['total_profiles']) }}</span>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">{{ $stats['verified_users'] }} verified</span>
            </div>
            <div class="mt-3 text-xs text-slate-500">Includes active biodata</div>
        </div>

        <!-- Pending Reports -->
        <a href="{{ route('admin.reports.index', ['status' => 'open']) }}" class="bg-white p-5 rounded-2xl border {{ $stats['pending_reports'] > 0 ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200' }} shadow-xs hover:border-amber-400 transition-all block">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                <span>Pending Reports</span>
                @if($stats['pending_reports'] > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                @endif
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold {{ $stats['pending_reports'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $stats['pending_reports'] }}</span>
                <span class="text-xs font-semibold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Requires review</span>
            </div>
            <div class="mt-3 text-xs text-slate-500">Safety & violation reports</div>
        </a>

        <!-- Pending Photos -->
        <a href="{{ route('admin.photos.index', ['status' => 'pending']) }}" class="bg-white p-5 rounded-2xl border {{ $stats['pending_photos'] > 0 ? 'border-purple-300 ring-2 ring-purple-100' : 'border-slate-200' }} shadow-xs hover:border-purple-400 transition-all block">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                <span>Photos Pending</span>
                @if($stats['pending_photos'] > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                @endif
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold {{ $stats['pending_photos'] > 0 ? 'text-purple-600' : 'text-slate-900' }}">{{ $stats['pending_photos'] }}</span>
                <span class="text-xs font-semibold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">Unreviewed</span>
            </div>
            <div class="mt-3 text-xs text-slate-500">Awaiting visual approval</div>
        </a>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-xs">
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Interests Sent</div>
            <div class="mt-2 text-2xl font-bold">{{ number_format($stats['interests_sent']) }}</div>
            <div class="mt-2 text-xs text-slate-400">Total expressions of interest</div>
        </div>

        <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-xs">
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Connected Matches</div>
            <div class="mt-2 text-2xl font-bold text-rose-400">{{ number_format($stats['successful_connections']) }}</div>
            <div class="mt-2 text-xs text-slate-400">Mutual accepted interests</div>
        </div>

        <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-xs">
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Suspended Accounts</div>
            <div class="mt-2 text-2xl font-bold text-amber-400">{{ number_format($stats['suspended_accounts']) }}</div>
            <div class="mt-2 text-xs text-slate-400">Restricted for safety</div>
        </div>

        <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-xs">
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wider">Avg Report Resolution</div>
            <div class="mt-2 text-2xl font-bold text-emerald-400">{{ $stats['avg_resolution_hours'] }}h</div>
            <div class="mt-2 text-xs text-slate-400">Average resolution turnaround</div>
        </div>
    </div>

    <!-- Active Action Queues -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Reports Queue -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900">Urgent Reports Queue</h2>
                    <p class="text-xs text-slate-500">Unresolved safety reports from users</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-rose-600 hover:text-rose-700">View All &rarr;</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentReports as $report)
                    <div class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                    {{ str_replace('_', ' ', $report->reason->value) }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $report->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-800 font-medium truncate">
                                Against: <span class="text-slate-900 font-bold">{{ $report->reportedUser?->name ?? 'Deleted User' }}</span>
                                <span class="text-slate-400 text-xs">by {{ $report->reporter?->name ?? 'Anonymous' }}</span>
                            </div>
                            @if($report->details)
                                <p class="text-xs text-slate-500 truncate mt-0.5">"{{ $report->details }}"</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.reports.show', $report) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold whitespace-nowrap">
                            Review
                        </a>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-sm">
                        No pending reports! Moderation inbox is clean.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Verifications Queue -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900">Verification Requests</h2>
                    <p class="text-xs text-slate-500">Members requesting identity and ladder verification</p>
                </div>
                <a href="{{ route('admin.verifications.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View All &rarr;</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentVerifications as $verification)
                    <div class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Target Level {{ is_object($verification->level) ? $verification->level->value : $verification->level }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $verification->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-800 font-medium truncate">
                                User: <span class="text-slate-900 font-bold">{{ $verification->user?->name ?? 'Unknown' }}</span>
                                <span class="text-slate-400 text-xs">({{ $verification->user?->email }})</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.verifications.show', $verification) }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold whitespace-nowrap">
                            Review Request
                        </a>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-sm">
                        No pending verification requests.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Photos Moderation Queue Preview -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Photos Pending Moderation</h2>
                <p class="text-xs text-slate-500">Review images uploaded by candidates before public blur delivery</p>
            </div>
            <a href="{{ route('admin.photos.index') }}" class="text-xs font-semibold text-purple-600 hover:text-purple-700">Open Moderation Panel &rarr;</a>
        </div>

        <div class="p-5">
            @if($recentPhotos->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-4">
                    @foreach($recentPhotos as $photo)
                        <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 group flex flex-col justify-between">
                            <div class="aspect-square bg-slate-200 relative overflow-hidden">
                                <img src="{{ route('admin.photos.preview', $photo) }}" alt="Preview" class="w-full h-full object-cover">
                                @if($photo->is_primary)
                                    <span class="absolute top-1 left-1 bg-amber-500 text-slate-900 text-[10px] font-black px-1.5 py-0.5 rounded">PRIMARY</span>
                                @endif
                            </div>
                            <div class="p-2 text-center text-xs">
                                <div class="font-medium text-slate-800 truncate">{{ $photo->profile?->full_name_en ?? 'Profile' }}</div>
                                <div class="mt-2 flex gap-1 justify-center">
                                    <form action="{{ route('admin.photos.approve', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Approve" class="p-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[10px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.photos.reject', $photo) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Reject" class="p-1 rounded bg-rose-600 hover:bg-rose-700 text-white text-[10px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-slate-400 text-sm">
                    All uploaded photos have been moderated!
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Recently Registered Users</h2>
                <p class="text-xs text-slate-500">New member signups</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Browse All Users &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Verification</th>
                        <th class="px-6 py-3">Registered</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentUsers as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }} &bull; {{ $user->phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 capitalize">
                                    {{ $user->role->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->status === \App\Enums\AccountStatus::Active)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                                @elseif($user->status === \App\Enums\AccountStatus::Suspended)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Suspended</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 capitalize">{{ $user->status->value }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    Level {{ $user->profile?->verification_level ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
