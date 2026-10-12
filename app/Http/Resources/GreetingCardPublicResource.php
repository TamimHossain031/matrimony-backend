<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GreetingCardPublicResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'occasion' => $this->occasion,
            'recipient_name' => $this->recipient_name,
            'sender_name' => $this->sender_name,
            'title' => $this->title,
            'message' => $this->message,
            'theme_settings' => $this->theme_settings,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'shares_count' => $this->shares_count,
            'reactions_count' => $this->reactions_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'template' => new GreetingTemplateResource($this->whenLoaded('template', $this->template)),
        ];
    }
}
