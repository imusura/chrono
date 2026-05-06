<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('ticket_types', 'name')->where('project_id', $project->id)],
            'color' => ['required', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
