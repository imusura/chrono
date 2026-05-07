<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NonWorkingDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organisation_id' => $this->organisation_id,
            'date'            => $this->date->format('Y-m-d'),
            'name'            => $this->name,
            'is_public'       => $this->isPublic(),
        ];
    }
}
