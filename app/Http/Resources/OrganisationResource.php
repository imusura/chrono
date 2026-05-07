<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'time_entry_mode' => $this->time_entry_mode->value,
            'country_code'    => $this->country_code,
            'created_at'      => $this->created_at,
        ];
    }
}
