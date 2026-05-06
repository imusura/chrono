<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketStatusResource extends JsonResource
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
            'is_active' => $this->is_active,
            'sort_order' => $this->whenPivotLoaded('ticket_type_statuses', fn () => $this->pivot->sort_order),
            'is_final' => $this->whenPivotLoaded('ticket_type_statuses', fn () => (bool) $this->pivot->is_final),
        ];
    }
}
