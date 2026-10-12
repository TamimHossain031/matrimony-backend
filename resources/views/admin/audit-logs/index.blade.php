@extends('admin.layouts.admin')

@section('title', 'Audit Trail')
@section('page_title', 'Administrative Audit Trail (§16, §23)')

@section('content')
<div class="space-y-6">
    <!-- Filter Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <!-- Action Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Action Type</label>
                <select name="action" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Staff Actor Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Staff Member</label>
                <select name="actor_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500 text-sm bg-white">
                    <option value="">All Staff Members</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ request('actor_id') == $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit -->
            <div>
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl">
                    Filter Logs
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Timestamp</th>
                        <th class="px-6 py-3.5">Actor</th>
                        <th class="px-6 py-3.5">Action</th>
                        <th class="px-6 py-3.5">Subject</th>
                        <th class="px-6 py-3.5">IP Address</th>
                        <th class="px-6 py-3.5">Context Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-500">
                                <div>{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400 font-sans">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap font-sans">
                                @if($log->actor)
                                    <span class="font-bold text-slate-900">{{ $log->actor->name }}</span>
                                    <span class="text-slate-400 text-xs block">ID: #{{ $log->actor_id }}</span>
                                @else
                                    <span class="text-slate-400">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-600">
                                @if($log->subject_type)
                                    <div>{{ class_basename($log->subject_type) }}</div>
                                    <div class="text-[10px] text-slate-400">ID: #{{ $log->subject_id }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-slate-500">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="px-6 py-3.5">
                                @if($log->context && count($log->context) > 0)
                                    <details class="cursor-pointer">
                                        <summary class="text-rose-600 font-sans font-semibold hover:underline">
                                            View Details ({{ count($log->context) }} fields)
                                        </summary>
                                        <pre class="mt-2 p-2 rounded bg-slate-900 text-emerald-400 text-[10px] overflow-x-auto max-w-md">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-slate-400 font-sans">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-sans">
                                No audit log entries recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
