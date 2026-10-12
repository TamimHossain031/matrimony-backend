@extends('admin.layouts.admin')

@section('title', 'Verification Ladder')
@section('page_title', 'Member Verification Ladder Review (§15)')

@section('content')
<div class="space-y-6">
    <!-- Status Tabs -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
            @php $currentStatus = request('status', ''); @endphp
            <a href="{{ route('admin.verifications.index') }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                All ({{ $statusCounts['all'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'pending']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'pending' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Pending ({{ $statusCounts['pending'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'approved']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'approved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Approved ({{ $statusCounts['approved'] }})
            </a>
            <a href="{{ route('admin.verifications.index', ['status' => 'rejected']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'rejected' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Rejected ({{ $statusCounts['rejected'] }})
            </a>
        </div>

        <!-- Level Filter -->
        <form method="GET" action="{{ route('admin.verifications.index') }}" class="flex items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <select name="level" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                <option value="">All Verification Levels</option>
                <option value="1" {{ request('level') == 1 ? 'selected' : '' }}>Level 1: Phone + Email</option>
                <option value="2" {{ request('level') == 2 ? 'selected' : '' }}>Level 2: NID Verified</option>
                <option value="3" {{ request('level') == 3 ? 'selected' : '' }}>Level 3: Selfie Match</option>
                <option value="4" {{ request('level') == 4 ? 'selected' : '' }}>Level 4: Manual Review</option>
            </select>
        </form>
    </div>

    <!-- Verifications Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Candidate / User</th>
                        <th class="px-6 py-3.5">Target Level</th>
                        <th class="px-6 py-3.5">Current Level</th>
                        <th class="px-6 py-3.5">Submitted</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Reviewer</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($verifications as $v)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $v->user?->name ?? 'User #' . $v->user_id }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $v->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $targetLevelNum = is_object($v->level) ? $v->level->value : (int) $v->level;
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    Level {{ $targetLevelNum }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                                Level {{ $v->user?->profile?->verification_level ?? 0 }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $v->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($v->status === 'pending')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Pending Review</span>
                                @elseif($v->status === 'approved')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Approved</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Rejected</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $v->reviewer?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.verifications.show', $v) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs whitespace-nowrap">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No verification requests in this category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($verifications->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $verifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
