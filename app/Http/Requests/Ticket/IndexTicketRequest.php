<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'status_id' => ['sometimes', 'integer', 'exists:ticket_statuses,id'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'type_id' => ['sometimes', 'integer', 'exists:ticket_types,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'sort' => ['sometimes', Rule::in(['created_at', 'updated_at', 'priority', 'status_id', 'title'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
