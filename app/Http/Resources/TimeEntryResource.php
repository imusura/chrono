<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'activity_id'      => $this->activity_id,
            'activity'         => [
                'id'    => $this->activity->id,
                'name'  => $this->activity->name,
                'color' => $this->activity->color,
            ],
            'date'             => $this->date->toDateString(),
            'started_at'       => $this->started_at ? substr($this->started_at, 0, 5) : null,
            'ended_at'         => $this->ended_at ? substr($this->ended_at, 0, 5) : null,
            'duration_minutes' => $this->duration_minutes,
            'notes'            => $this->notes,
        ];
    }
}
