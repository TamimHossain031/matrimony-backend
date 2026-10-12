<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GreetingTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'occasion' => $this->occasion,
            'description' => $this->description,
            'thumbnail_path' => $this->thumbnail_path,
            'design_config' => $this->design_config,
            'sort_order' => $this->sort_order,
        ];
    }
}
