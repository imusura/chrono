<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'number' => $this->number,
            'reference_id' => $this->reference_id,
            'title' => $this->title,
            'content' => $this->content,
            'type_id' => $this->type_id,
            'status_id' => $this->status_id,
            'type' => new TicketTypeResource($this->whenLoaded('ticketType')),
            'status' => new TicketStatusResource($this->whenLoaded('ticketStatus')),
            'priority' => $this->priority->value,
            'custom_fields' => $this->custom_fields,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'activities' => TicketActivityResource::collection($this->whenLoaded('activities')),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'submitter_email' => $this->submitter_email,
            'submitter_name' => $this->submitter_name,
            'metadata' => $this->metadata,
            'created_via_api_client_id' => $this->created_via_api_client_id,
            'api_client' => $this->whenLoaded('apiClient', fn () => $this->apiClient ? [
                'id' => $this->apiClient->id,
                'name' => $this->apiClient->name,
            ] : null),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
