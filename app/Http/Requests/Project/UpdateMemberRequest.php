<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Enums\ProjectRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::enum(ProjectRole::class)],
        ];
    }
}
