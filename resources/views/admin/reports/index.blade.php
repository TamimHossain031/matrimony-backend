@extends('admin.layouts.admin')

@section('title', 'Reports & Safety')
@section('page_title', 'Member Reports & Moderation Queue')

@section('content')
<div class="space-y-6">
    <!-- Status Tabs & Filter -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <!-- Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0">
            @php $currentStatus = request('status', ''); @endphp
            <a href="{{ route('admin.reports.index') }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === '' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                All ({{ $statusCounts['all'] }})
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'open']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'open' ? 'bg-amber-500 text-slate-900 shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Open ({{ $statusCounts['open'] }})
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'reviewing']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'reviewing' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Reviewing ({{ $statusCounts['reviewing'] }})
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'resolved' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Resolved ({{ $statusCounts['resolved'] }})
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'dismissed']) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $currentStatus === 'dismissed' ? 'bg-slate-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Dismissed ({{ $statusCounts['dismissed'] }})
            </a>
        </div>

        <!-- Reason Filter Form -->
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <select name="reason" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-300 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                <option value="">All Reasons</option>
                @foreach(\App\Enums\ReportReason::cases() as $r)
                    <option value="{{ $r->value }}" {{ request('reason') === $r->value ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', ucfirst($r->value)) }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Reported User</th>
                        <th class="px-6 py-3.5">Report Reason</th>
                        <th class="px-6 py-3.5">Reporter</th>
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Handled By</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $report)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $report->reportedUser?->name ?? 'User #' . $report->reported_user_id }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $report->reportedUser?->profile?->public_id ?? 'No Profile ID' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                    {{ str_replace('_', ' ', $report->reason->value) }}
                                </span>
                                @if($report->details)
                                    <p class="text-xs text-slate-500 truncate max-w-xs mt-1">"{{ $report->details }}"</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="font-semibold text-slate-800">{{ $report->reporter?->name ?? 'User #' . $report->reporter_id }}</div>
                                <div class="text-slate-400 font-mono">{{ $report->reporter?->phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $report->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($report->status === 'open')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Open</span>
                                @elseif($report->status === 'reviewing')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Reviewing</span>
                                @elseif($report->status === 'resolved')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Resolved</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 capitalize">{{ $report->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $report->handler?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.reports.show', $report) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs whitespace-nowrap">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No reports found in this view.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
