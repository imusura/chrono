<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function ($validator) {
                $project = $this->route('project');
                if ($this->input('confirmation') !== $project->name) {
                    $validator->errors()->add('confirmation', 'The project name does not match.');
                }
            },
        ];
    }
}
