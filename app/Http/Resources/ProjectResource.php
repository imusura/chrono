<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'prefix' => $this->prefix,
            'default_assignee_id' => $this->default_assignee_id,
            'role' => $this->whenPivotLoaded('project_user', fn () => $this->pivot->role),
            'tickets_count' => $this->whenCounted('tickets'),
            'closed_tickets_count' => $this->whenCounted('closed_tickets'),
            'active_tickets_count' => $this->whenCounted('active_tickets'),
            'members_count' => $this->whenCounted('members'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
