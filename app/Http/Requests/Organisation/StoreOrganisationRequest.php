<?php

namespace App\Http\Requests\Organisation;

use App\Enums\TimeEntryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrganisationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'time_entry_mode' => ['required', new Enum(TimeEntryMode::class)],
            'country_code'    => ['required', 'string', 'size:2'],
        ];
    }
}
