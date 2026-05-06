<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'type_id' => ['sometimes', 'integer', 'exists:ticket_types,id'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
