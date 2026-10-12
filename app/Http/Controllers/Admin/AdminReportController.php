<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::with(['reporter.profile', 'reportedUser.profile', 'handler'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        $reports = $query->paginate(15)->withQueryString();

        $statusCounts = [
            'all' => Report::count(),
            'open' => Report::where('status', 'open')->count(),
            'reviewing' => Report::where('status', 'reviewing')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
        ];

        return view('admin.reports.index', compact('reports', 'statusCounts'));
    }

    public function show(Report $report): View
    {
        $report->load(['reporter.profile', 'reportedUser.profile', 'handler']);

        $previousReportsCount = Report::where('reported_user_id', $report->reported_user_id)
            ->where('id', '!=', $report->id)
            ->count();

        return view('admin.reports.show', compact('report', 'previousReportsCount'));
    }

    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'];
        $isTerminal = in_array($status, ['resolved', 'dismissed'], true);

        $report->update([
            'status' => $status,
            'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
            'handled_by' => auth()->id(),
            'resolved_at' => $isTerminal ? now() : ($status === 'open' ? null : $report->resolved_at),
        ]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'report.status_updated',
            subject: $report,
            context: [
                'status' => $status,
                'resolution_note' => $validated['resolution_note'] ?? null,
            ]
        );

        return back()->with('success', "Report status updated to '{$status}'.");
    }

    public function suspendUserAndResolve(Request $request, Report $report): RedirectResponse
    {
        $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $reportedUser = $report->reportedUser;
        if (! $reportedUser) {
            return back()->with('error', 'Reported user does not exist.');
        }

        if ($reportedUser->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend yourself.');
        }

        $resolutionNote = $request->input('resolution_note', 'Account suspended following report investigation.');

        DB::transaction(function () use ($report, $reportedUser, $resolutionNote) {
            $reportedUser->update(['status' => AccountStatus::Suspended]);

            $report->update([
                'status' => 'resolved',
                'resolution_note' => $resolutionNote,
                'handled_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            AuditLog::record(
                actor: auth()->user(),
                action: 'user.suspended_via_report',
                subject: $reportedUser,
                context: ['report_id' => $report->id, 'reason' => $report->reason->value]
            );

            AuditLog::record(
                actor: auth()->user(),
                action: 'report.resolved_with_suspension',
                subject: $report,
                context: ['reported_user_id' => $reportedUser->id, 'resolution_note' => $resolutionNote]
            );
        });

        return back()->with('success', "User '{$reportedUser->name}' has been suspended and report resolved.");
    }
}
