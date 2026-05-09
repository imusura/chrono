<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'leave_type_id'    => $this->leave_type_id,
            'approved_by'      => $this->approved_by,
            'start_date'       => $this->start_date->toDateString(),
            'end_date'         => $this->end_date->toDateString(),
            'days_count'       => (float) $this->days_count,
            'status'           => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
