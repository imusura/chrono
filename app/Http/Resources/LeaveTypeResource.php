<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'has_allocation'    => $this->has_allocation,
            'requires_approval' => $this->requires_approval,
            'allow_carryover'   => $this->allow_carryover,
        ];
    }
}
