<?php

namespace App\Http\Requests\TimeEntry;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'started_at' => ['required', 'date_format:H:i'],
            'ended_at' => ['required', 'date_format:H:i', 'after:started_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
