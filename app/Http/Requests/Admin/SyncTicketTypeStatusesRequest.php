<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncTicketTypeStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*.status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
            'statuses.*.sort_order' => ['required', 'integer', 'min:0'],
            'statuses.*.is_final' => ['sometimes', 'boolean'],
        ];
    }
}
