<?php

namespace App\Http\Requests\TimeEntry;

use App\Enums\TimeEntryMode;
use App\Models\TimeEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTimeEntryRequest extends FormRequest
{
    public function rules(): array
    {
        $mode = $this->user()->organisation?->time_entry_mode ?? TimeEntryMode::Range;

        if ($mode === TimeEntryMode::Duration) {
            return [
                'activity_id'      => ['required', 'integer', Rule::exists('activities', 'id')->where('organisation_id', $this->user()->organisation_id)],
                'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'notes'            => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [
            'activity_id' => ['required', 'integer', Rule::exists('activities', 'id')->where('organisation_id', $this->user()->organisation_id)],
            'started_at'  => ['required', 'date_format:H:i'],
            'ended_at'    => ['required', 'date_format:H:i', 'after:started_at'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        $mode = $this->user()->organisation?->time_entry_mode ?? TimeEntryMode::Range;

        if ($mode === TimeEntryMode::Range) {
            return [
                function (Validator $validator) {
                    if ($validator->errors()->isNotEmpty()) {
                        return;
                    }

                    /** @var TimeEntry $timeEntry */
                    $timeEntry = $this->route('timeEntry');

                    $overlap = TimeEntry::where('user_id', $this->user()->id)
                        ->where('date', $timeEntry->date)
                        ->where('id', '!=', $timeEntry->id)
                        ->where('started_at', '<', $this->input('ended_at'))
                        ->where('ended_at', '>', $this->input('started_at'))
                        ->exists();

                    if ($overlap) {
                        $validator->errors()->add('started_at', __('validation.custom.started_at.overlap'));
                    }
                },
            ];
        }

        return [];
    }
}
