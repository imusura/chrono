<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use App\Models\TicketTypeField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'type_id' => ['required', 'integer', Rule::exists('ticket_types', 'id')->where('project_id', $project->id)->where('is_active', true)],
            'custom_fields' => ['sometimes', 'array'],
            'custom_fields.*' => ['nullable'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator) {
                $typeId = $this->input('type_id');
                if (! $typeId) {
                    return;
                }

                $fields = TicketTypeField::where('ticket_type_id', $typeId)->where('is_active', true)->get();
                $customFields = $this->input('custom_fields', []);

                foreach ($fields as $field) {
                    if ($field->is_required && empty($customFields[$field->id])) {
                        $validator->errors()->add("custom_fields.{$field->id}", "{$field->label} is required.");
                    }
                }
            },
        ];
    }
}
