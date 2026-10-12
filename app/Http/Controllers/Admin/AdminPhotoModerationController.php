<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PhotoModerationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateBlurredPhoto;
use App\Models\AuditLog;
use App\Models\ProfilePhoto;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPhotoModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        $query = ProfilePhoto::with(['profile.user'])->latest();

        if ($status !== 'all') {
            $query->where('moderation_status', $status);
        }

        $photos = $query->paginate(24)->withQueryString();

        $statusCounts = [
            'pending' => ProfilePhoto::where('moderation_status', PhotoModerationStatus::Pending)->count(),
            'approved' => ProfilePhoto::where('moderation_status', PhotoModerationStatus::Approved)->count(),
            'rejected' => ProfilePhoto::where('moderation_status', PhotoModerationStatus::Rejected)->count(),
            'all' => ProfilePhoto::count(),
        ];

        return view('admin.photos.index', compact('photos', 'statusCounts', 'status'));
    }

    public function approve(ProfilePhoto $photo): RedirectResponse
    {
        $photo->update(['moderation_status' => PhotoModerationStatus::Approved]);

        if (! $photo->blurred_path) {
            GenerateBlurredPhoto::dispatch($photo);
        }

        $photo->profile->forceFill(['has_photo' => true])->saveQuietly();

        AuditLog::record(
            actor: auth()->user(),
            action: 'photo.approved',
            subject: $photo,
            context: ['profile_id' => $photo->profile_id]
        );

        return back()->with('success', 'Photo approved successfully.');
    }

    public function reject(ProfilePhoto $photo): RedirectResponse
    {
        $photo->update(['moderation_status' => PhotoModerationStatus::Rejected]);

        $hasOtherApproved = $photo->profile->photos()
            ->where('moderation_status', PhotoModerationStatus::Approved)
            ->exists();

        if (! $hasOtherApproved) {
            $photo->profile->forceFill(['has_photo' => false])->saveQuietly();
        }

        AuditLog::record(
            actor: auth()->user(),
            action: 'photo.rejected',
            subject: $photo,
            context: ['profile_id' => $photo->profile_id]
        );

        return back()->with('success', 'Photo marked as rejected.');
    }

    public function destroy(ProfilePhoto $photo): RedirectResponse
    {
        Storage::disk($photo->disk)->delete(array_filter([$photo->path, $photo->blurred_path]));
        $profile = $photo->profile;

        $photo->delete();

        $hasApproved = $profile->photos()
            ->where('moderation_status', PhotoModerationStatus::Approved)
            ->exists();
        $profile->forceFill(['has_photo' => $hasApproved])->saveQuietly();

        AuditLog::record(
            actor: auth()->user(),
            action: 'photo.deleted',
            subject: $photo,
            context: ['profile_id' => $profile->id]
        );

        return back()->with('success', 'Photo deleted permanently.');
    }

    public function batchAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'photo_ids' => ['required', 'array'],
            'photo_ids.*' => ['exists:profile_photos,id'],
        ]);

        $status = $validated['action'] === 'approve'
            ? PhotoModerationStatus::Approved
            : PhotoModerationStatus::Rejected;

        $photos = ProfilePhoto::with('profile')->whereIn('id', $validated['photo_ids'])->get();

        foreach ($photos as $photo) {
            $photo->update(['moderation_status' => $status]);

            if ($status === PhotoModerationStatus::Approved) {
                if (! $photo->blurred_path) {
                    GenerateBlurredPhoto::dispatch($photo);
                }
                $photo->profile->forceFill(['has_photo' => true])->saveQuietly();
            }
        }

        AuditLog::record(
            actor: auth()->user(),
            action: "photo.batch_{$validated['action']}",
            context: ['photo_ids' => $validated['photo_ids'], 'count' => count($photos)]
        );

        $count = count($photos);

        return back()->with('success', "Batch {$validated['action']} applied to {$count} photos.");
    }

    public function preview(ProfilePhoto $photo): StreamedResponse
    {
        return $this->streamPhoto($photo->disk, $photo->path);
    }

    public function previewBlurred(ProfilePhoto $photo): StreamedResponse
    {
        abort_unless($photo->blurred_path, 404, 'Blurred image not available.');

        return $this->streamPhoto($photo->disk, $photo->blurred_path);
    }

    private function streamPhoto(string $diskName, string $path): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404, 'Photo file not found on disk.');

        $mimeType = $disk->mimeType($path);
        $file = $disk->get($path);

        return response()->streamDownload(function () use ($file) {
            echo $file;
        }, basename($path), ['Content-Type' => $mimeType]);
    }
}
