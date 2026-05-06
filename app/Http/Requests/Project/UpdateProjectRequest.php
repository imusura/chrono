<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('projects', 'slug')->ignore($project), 'regex:/^[a-z0-9-]+$/'],
            'prefix' => ['sometimes', 'string', 'max:10', Rule::unique('projects', 'prefix')->ignore($project), 'regex:/^[A-Z0-9]+$/'],
            'default_assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
        ];
    }
}
