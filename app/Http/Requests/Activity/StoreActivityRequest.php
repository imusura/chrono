<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    private function resolveOrgId(): int
    {
        return $this->user()->is_super_admin
            ? $this->integer('organisation_id')
            : (int) $this->user()->organisation_id;
    }

    public function rules(): array
    {
        return [
            'organisation_id' => ['nullable', 'integer', 'exists:organisations,id'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('organisation_id', $this->resolveOrgId())],
        ];
    }
}
