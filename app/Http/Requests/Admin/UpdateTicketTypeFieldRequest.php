<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTypeFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:255'],
            'field_type' => ['sometimes', 'string', Rule::in(['text', 'number', 'date', 'select', 'checkbox', 'textarea'])],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
