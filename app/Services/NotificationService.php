<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;

/**
 * In-app notifications (blueprint §17). Respects per-type preferences; email/push
 * channels are future work, so only the in-app channel is delivered for now.
 */
class NotificationService
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function push(User $user, string $type, string $title, string $message, array $data = []): ?Notification
    {
        if (! $this->wantsInApp($user, $type)) {
            return null;
        }

        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function wantsInApp(User $user, string $type): bool
    {
        $pref = NotificationPreference::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        // Default on when no explicit preference row exists.
        return $pref?->in_app ?? true;
    }
}
