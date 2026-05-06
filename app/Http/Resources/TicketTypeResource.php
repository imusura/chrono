<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'statuses' => TicketStatusResource::collection($this->whenLoaded('statuses')),
            'fields' => TicketTypeFieldResource::collection($this->whenLoaded('fields')),
        ];
    }
}
