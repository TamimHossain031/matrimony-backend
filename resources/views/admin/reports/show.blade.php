@extends('admin.layouts.admin')

@section('title', 'Report #' . $report->id)
@section('page_title', 'Report Investigation #' . $report->id)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 uppercase">
                    {{ str_replace('_', ' ', $report->reason->value) }}
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $report->status === 'open' ? 'bg-amber-100 text-amber-800' : ($report->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800') }}">
                    Status: {{ $report->status }}
                </span>
            </div>
            <div class="mt-2 text-xs text-slate-400">Filed on {{ $report->created_at->format('F d, Y \a\t H:i') }}</div>
        </div>

        @if($report->resolved_at)
            <div class="text-right text-xs text-slate-500">
                Resolved on: <strong class="text-slate-800">{{ $report->resolved_at->format('M d, Y H:i') }}</strong><br>
                by: <strong class="text-slate-800">{{ $report->handler?->name ?? 'Staff' }}</strong>
            </div>
        @endif
    </div>

    <!-- Parties Involved: Reporter vs Reported -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Reported User -->
        <div class="bg-white p-6 rounded-2xl border border-rose-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">Reported Candidate</span>
                @if($report->reportedUser)
                    <a href="{{ route('admin.users.show', $report->reportedUser) }}" class="text-xs font-bold text-slate-900 hover:underline">
                        Manage User &rarr;
                    </a>
                @endif
            </div>

            @if($report->reportedUser)
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-700 font-extrabold text-xl flex items-center justify-center border border-rose-200">
                        {{ strtoupper(substr($report->reportedUser->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $report->reportedUser->name }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $report->reportedUser->phone }} &bull; {{ $report->reportedUser->email }}</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $report->reportedUser->status === \App\Enums\AccountStatus::Suspended ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ ucfirst($report->reportedUser->status->value) }}
                            </span>
                            @if($previousReportsCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    {{ $previousReportsCount }} other report(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="text-slate-400 text-sm">Account has been deleted.</div>
            @endif
        </div>

        <!-- Reporter -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Reporting Member</span>
                @if($report->reporter)
                    <a href="{{ route('admin.users.show', $report->reporter) }}" class="text-xs font-bold text-slate-900 hover:underline">
                        View Profile &rarr;
                    </a>
                @endif
            </div>

            @if($report->reporter)
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-700 font-extrabold text-xl flex items-center justify-center">
                        {{ strtoupper(substr($report->reporter->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $report->reporter->name }}</div>
                        <div class="text-xs text-slate-500 font-mono">{{ $report->reporter->phone }} &bull; {{ $report->reporter->email }}</div>
                        <div class="mt-1 text-xs text-slate-400">Registered on {{ $report->reporter->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            @else
                <div class="text-slate-400 text-sm">Anonymous or deleted account.</div>
            @endif
        </div>
    </div>

    <!-- Report Details -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <h3 class="font-bold text-slate-900 text-base mb-3">Submitted Statement / Description</h3>
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-sm whitespace-pre-line">
            {{ $report->details ?? 'No additional details provided by the reporter.' }}
        </div>
    </div>

    <!-- Moderation Resolution Panel -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        <h3 class="font-bold text-slate-900 text-base">Resolution & Moderation Action</h3>

        <!-- Form 1: Standard Status & Notes -->
        <form action="{{ route('admin.reports.status', $report) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Update Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="open" {{ $report->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="reviewing" {{ $report->status === 'reviewing' ? 'selected' : '' }}>In Review</option>
                        <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="dismissed" {{ $report->status === 'dismissed' ? 'selected' : '' }}>Dismissed (No violation)</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Resolution Note</label>
                    <input type="text"
                           name="resolution_note"
                           value="{{ $report->resolution_note }}"
                           placeholder="Explain findings or action taken..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl">
                    Save Resolution Update
                </button>
            </div>
        </form>

        <!-- Form 2: Direct Suspend User & Resolve Report -->
        @if($report->reportedUser && $report->reportedUser->status !== \App\Enums\AccountStatus::Suspended)
            <div class="pt-6 border-t border-slate-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-rose-50/50 p-4 rounded-xl border-dashed">
                <div>
                    <h4 class="font-bold text-rose-900 text-sm">Enforce Safety Sanction</h4>
                    <p class="text-xs text-rose-700">Immediately suspend the reported user's account and mark this report as resolved.</p>
                </div>
                <form action="{{ route('admin.reports.action-suspend', $report) }}" method="POST" onsubmit="return confirm('Suspend user {{ $report->reportedUser->name }} and resolve report?');">
                    @csrf
                    <input type="hidden" name="resolution_note" value="Violated community safety standards. Account suspended.">
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs whitespace-nowrap">
                        Suspend User & Resolve Report
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
