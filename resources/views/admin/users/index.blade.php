@extends('admin.layouts.admin')

@section('title', 'Users & Accounts')
@section('page_title', 'User Accounts Management')

@section('content')
<div class="space-y-6">
    <!-- Filters Header -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <!-- Search -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Search User or Profile</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Name, email, phone, public ID (e.g. BD-)..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm">
            </div>

            <!-- Role Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Role</label>
                <select name="role" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                    <option value="">All Roles</option>
                    @foreach(\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>
                            {{ ucfirst($role->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Status</label>
                    <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                        <option value="">All Statuses</option>
                        @foreach(\App\Enums\AccountStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>
                                {{ ucfirst($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl shrink-0">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">User Account</th>
                        <th class="px-6 py-3.5">Role</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Verification</th>
                        <th class="px-6 py-3.5">Profile Public ID</th>
                        <th class="px-6 py-3.5">Registered</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-sm shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900 truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500 truncate">{{ $user->email }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ $user->phone }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->role === \App\Enums\UserRole::Admin)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">Admin</span>
                                @elseif($user->role === \App\Enums\UserRole::Moderator)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Moderator</span>
                                @elseif($user->role === \App\Enums\UserRole::Guardian)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">Guardian</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">User</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->status === \App\Enums\AccountStatus::Active)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Active</span>
                                @elseif($user->status === \App\Enums\AccountStatus::Suspended)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Suspended</span>
                                @elseif($user->status === \App\Enums\AccountStatus::Hidden)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Hidden</span>
                                @elseif($user->status === \App\Enums\AccountStatus::Deactivated)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">Deactivated</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-300 text-slate-800">{{ $user->status->value }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php $level = $user->profile?->verification_level ?? 0; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $level > 0 ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-500' }}">
                                    Level {{ $level }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono">
                                @if($user->profile)
                                    <a href="{{ route('admin.profiles.show', $user->profile) }}" class="text-rose-600 hover:underline font-semibold">
                                        {{ $user->profile->public_id }}
                                    </a>
                                @else
                                    <span class="text-slate-400">No profile</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.show', $user) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-xs">
                                        Manage
                                    </a>
                                    @if($user->status === \App\Enums\AccountStatus::Suspended)
                                        <form action="{{ route('admin.users.restore', $user) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs">
                                                Restore
                                            </button>
                                        </form>
                                    @elseif($user->status === \App\Enums\AccountStatus::Active && $user->id !== auth()->id())
                                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST" onsubmit="return confirm('Suspend this user?')">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs">
                                                Suspend
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No users found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
