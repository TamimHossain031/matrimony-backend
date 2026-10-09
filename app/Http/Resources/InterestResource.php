<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;

        // The "other party" from the viewer's perspective, only if its user
        // relation was eager-loaded by the caller.
        $counterpartUser = $this->sender_id === $viewerId
            ? ($this->relationLoaded('receiver') ? $this->receiver : null)
            : ($this->relationLoaded('sender') ? $this->sender : null);

        $counterpartProfile = $counterpartUser instanceof User && $counterpartUser->relationLoaded('profile')
            ? $counterpartUser->profile
            : null;

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'message' => $this->message,
            'direction' => $this->sender_id === $viewerId ? 'sent' : 'received',
            'counterpart' => $counterpartProfile ? new ProfileCardResource($counterpartProfile) : null,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
