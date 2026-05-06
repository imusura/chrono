<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketTypeField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'user_id' => $this->user_id,
            'field' => $this->field,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'user' => $this->user_id ? new UserResource($this->whenLoaded('user')) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->field === 'status') {
            $data['old_color'] = $this->old_value ? TicketStatus::where('name', $this->old_value)->value('color') : null;
            $data['new_color'] = $this->new_value ? TicketStatus::where('name', $this->new_value)->value('color') : null;
        }

        if ($this->field === 'type') {
            $data['old_color'] = $this->old_value ? TicketType::where('name', $this->old_value)->value('color') : null;
            $data['new_color'] = $this->new_value ? TicketType::where('name', $this->new_value)->value('color') : null;
        }

        if (str_starts_with($this->field, 'custom_field:')) {
            $fieldId = (int) str_replace('custom_field:', '', $this->field);
            $data['field_label'] = TicketTypeField::find($fieldId)?->label;
        }

        return $data;
    }
}
