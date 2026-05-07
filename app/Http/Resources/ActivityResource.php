<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'role_ids' => $this->whenLoaded('roles', fn () => $this->roles->pluck('id')),
        ];
    }
}
