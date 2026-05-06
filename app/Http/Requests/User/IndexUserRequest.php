<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\ProjectRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', Rule::enum(ProjectRole::class)],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
