<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('organisation_id', $this->user()->organisation_id),
            ],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'amount'        => ['required', 'numeric', 'not_in:0'],
            'date'          => ['required', 'date_format:Y-m-d'],
            'note'          => ['required', 'string', 'max:1000'],
        ];
    }
}
