<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Enums\ProjectRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', Rule::enum(ProjectRole::class)],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator) {
                $user = \App\Models\User::where('email', $this->input('email'))->first();
                if (! $user) {
                    return;
                }

                $project = $this->route('project');
                if ($project->members()->where('user_id', $user->id)->exists()) {
                    $validator->errors()->add('email', 'This user is already a member of this project.');
                }
            },
        ];
    }
}
