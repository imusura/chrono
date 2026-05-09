<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'leave_type_id'    => $this->leave_type_id,
            'leave_request_id' => $this->leave_request_id,
            'type'             => $this->type->value,
            'amount'           => (float) $this->amount,
            'date'             => $this->date->toDateString(),
            'note'             => $this->note,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
