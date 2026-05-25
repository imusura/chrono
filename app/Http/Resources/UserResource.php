<?php

namespace App\Http\Resources;

use App\Enums\TimeEntryMode;
use App\Enums\VacationMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'organisation_id'  => $this->organisation_id,
            'contracted_hours' => (float) $this->contracted_hours,
            'time_entry_mode'  => $this->organisation?->time_entry_mode->value ?? TimeEntryMode::Range->value,
            'vacation_mode'    => $this->organisation?->vacation_mode->value ?? VacationMode::Simple->value,
            'is_admin'         => $this->is_admin,
            'is_super_admin'   => $this->is_super_admin,
            'role_ids'         => $this->whenLoaded('roles', fn () => $this->roles->pluck('id')),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
