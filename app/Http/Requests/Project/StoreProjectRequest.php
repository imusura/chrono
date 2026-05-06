<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Services\ProjectSetupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug', 'regex:/^[a-z0-9-]+$/'],
            'prefix' => ['required', 'string', 'max:10', 'unique:projects,prefix', 'regex:/^[A-Z0-9]+$/'],
            'template' => ['required', 'string', Rule::in(array_keys(ProjectSetupService::TEMPLATES))],
        ];
    }
}
