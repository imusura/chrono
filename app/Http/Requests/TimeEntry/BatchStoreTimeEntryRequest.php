<?php

namespace App\Http\Requests\TimeEntry;

use App\Enums\TimeEntryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchStoreTimeEntryRequest extends FormRequest
{
    public function rules(): array
    {
        $mode = $this->user()->organisation?->time_entry_mode ?? TimeEntryMode::Range;

        $entryRules = $mode === TimeEntryMode::Duration
            ? [
                'entries.*.activity_id'      => ['required', 'integer', Rule::exists('activities', 'id')->where('organisation_id', $this->user()->organisation_id)],
                'entries.*.date'             => ['required', 'date_format:Y-m-d'],
                'entries.*.duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'entries.*.notes'            => ['nullable', 'string', 'max:1000'],
            ]
            : [
                'entries.*.activity_id' => ['required', 'integer', Rule::exists('activities', 'id')->where('organisation_id', $this->user()->organisation_id)],
                'entries.*.date'        => ['required', 'date_format:Y-m-d'],
                'entries.*.started_at'  => ['required', 'date_format:H:i'],
                'entries.*.ended_at'    => ['required', 'date_format:H:i', 'after:entries.*.started_at'],
                'entries.*.notes'       => ['nullable', 'string', 'max:1000'],
            ];

        return array_merge(['entries' => ['required', 'array', 'min:1']], $entryRules);
    }
}
