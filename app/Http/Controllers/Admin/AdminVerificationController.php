<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Verification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Verification::with(['user.profile', 'reviewer'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        $verifications = $query->paginate(15)->withQueryString();

        $statusCounts = [
            'all' => Verification::count(),
            'pending' => Verification::where('status', 'pending')->count(),
            'approved' => Verification::where('status', 'approved')->count(),
            'rejected' => Verification::where('status', 'rejected')->count(),
        ];

        return view('admin.verifications.index', compact('verifications', 'statusCounts'));
    }

    public function show(Verification $verification): View
    {
        $verification->load(['user.profile.photos', 'reviewer']);

        return view('admin.verifications.show', compact('verification'));
    }

    public function approve(Request $request, Verification $verification): RedirectResponse
    {
        $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $targetLevel = is_object($verification->level) ? $verification->level->value : (int) $verification->level;

        DB::transaction(function () use ($verification, $targetLevel, $request) {
            $verification->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $request->input('review_note'),
            ]);

            if ($verification->user?->profile) {
                $verification->user->profile->update([
                    'verification_level' => max($verification->user->profile->verification_level, $targetLevel),
                ]);
            }

            AuditLog::record(
                actor: auth()->user(),
                action: 'verification.approved',
                subject: $verification,
                context: [
                    'user_id' => $verification->user_id,
                    'granted_level' => $targetLevel,
                    'review_note' => $request->input('review_note'),
                ]
            );
        });

        return back()->with('success', "Verification for Level {$targetLevel} has been approved.");
    }

    public function reject(Request $request, Verification $verification): RedirectResponse
    {
        $request->validate([
            'review_note' => ['required', 'string', 'max:500'],
        ]);

        $targetLevel = is_object($verification->level) ? $verification->level->value : (int) $verification->level;

        $verification->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        AuditLog::record(
            actor: auth()->user(),
            action: 'verification.rejected',
            subject: $verification,
            context: [
                'user_id' => $verification->user_id,
                'target_level' => $targetLevel,
                'review_note' => $request->input('review_note'),
            ]
        );

        return back()->with('success', "Verification for Level {$targetLevel} has been rejected.");
    }

    public function document(Verification $verification): StreamedResponse
    {
        abort_unless($verification->document_path, 404, 'No document submitted.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($verification->document_path), 404, 'Document file not found.');

        $mimeType = $disk->mimeType($verification->document_path);
        $file = $disk->get($verification->document_path);

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, basename($verification->document_path), ['Content-Type' => $mimeType]);
    }
}
