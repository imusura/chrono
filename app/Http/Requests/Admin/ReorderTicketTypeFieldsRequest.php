<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTicketTypeFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.id' => ['required', 'integer', 'exists:ticket_type_fields,id'],
            'fields.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
