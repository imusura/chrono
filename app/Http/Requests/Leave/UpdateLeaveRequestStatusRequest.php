<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeaveRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveRequestStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status'           => ['required', Rule::enum(LeaveRequestStatus::class)],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ];
    }
}
