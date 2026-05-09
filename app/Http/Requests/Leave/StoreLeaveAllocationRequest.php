<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveAllocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('organisation_id', $this->user()->organisation_id),
            ],
            'leave_type_id'        => ['required', 'integer', 'exists:leave_types,id'],
            'year'                 => ['required', 'integer', 'min:2000', 'max:2100'],
            'allowance'            => ['required', 'integer', 'min:0', 'max:365'],
            'carryover_amount'     => ['nullable', 'integer', 'min:0', 'max:365'],
            'carryover_expires_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
