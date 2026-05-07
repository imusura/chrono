<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
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
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('organisation_id', $this->resolveOrgId())],
            'contracted_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'is_admin' => ['boolean'],
        ];
    }
}
