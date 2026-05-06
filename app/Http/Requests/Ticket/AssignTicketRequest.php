<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'exists:users,id'],
        ];
    }
}
