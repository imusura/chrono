<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'user_id'              => $this->user_id,
            'leave_type_id'        => $this->leave_type_id,
            'year'                 => $this->year,
            'allowance'            => $this->allowance,
            'carryover_amount'     => $this->carryover_amount,
            'carryover_expires_on' => $this->carryover_expires_on?->toDateString(),
        ];
    }
}
