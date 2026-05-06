<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'default_ticket_type_id' => [
                'required',
                'integer',
                Rule::exists('ticket_types', 'id')->where('project_id', $project->id)->where('is_active', true),
            ],
        ];
    }
}
