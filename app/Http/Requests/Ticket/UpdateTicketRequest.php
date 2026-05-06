<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use App\Models\TicketTypeField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'status_id' => ['sometimes', 'integer', Rule::exists('ticket_statuses', 'id')->where('project_id', $project->id)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'type_id' => ['sometimes', 'integer', Rule::exists('ticket_types', 'id')->where('project_id', $project->id)->where('is_active', true)],
            'custom_fields' => ['sometimes', 'array'],
            'custom_fields.*' => ['nullable'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator) {
                $typeId = $this->input('type_id', $this->route('ticket')?->type_id);
                if (! $typeId || ! $this->has('custom_fields')) {
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
