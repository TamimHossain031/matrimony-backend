<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::with(['reporter.profile', 'reportedUser.profile', 'handler'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        $reports = $query->paginate(20);

        return response()->json($reports);
    }

    public function show(Report $report): JsonResponse
    {
        $report->load(['reporter.profile', 'reportedUser.profile', 'handler']);

        return response()->json(['data' => $report]);
    }

    public function updateStatus(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,reviewing,resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
            'suspend_user' => ['sometimes', 'boolean'],
        ]);

        $status = $validated['status'];
        $isTerminal = in_array($status, ['resolved', 'dismissed'], true);
        $shouldSuspend = ! empty($validated['suspend_user']) && $status === 'resolved';

        DB::transaction(function () use ($report, $status, $isTerminal, $shouldSuspend, $validated, $request) {
            $report->update([
                'status' => $status,
                'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
                'handled_by' => $request->user()->id,
                'resolved_at' => $isTerminal ? now() : $report->resolved_at,
            ]);

            if ($shouldSuspend && $report->reportedUser && $report->reportedUser->id !== $request->user()->id) {
                $report->reportedUser->update(['status' => AccountStatus::Suspended]);

                AuditLog::record(
                    actor: $request->user(),
                    action: 'user.suspended_via_report',
                    subject: $report->reportedUser,
                    context: ['report_id' => $report->id]
                );
            }

            AuditLog::record(
                actor: $request->user(),
                action: 'report.status_updated',
                subject: $report,
                context: [
                    'status' => $status,
                    'suspended_user' => $shouldSuspend,
                    'resolution_note' => $validated['resolution_note'] ?? null,
                ]
            );
        });

        return response()->json([
            'message' => 'Report updated successfully.',
            'data' => $report->fresh(['reporter', 'reportedUser', 'handler']),
        ]);
    }
}
