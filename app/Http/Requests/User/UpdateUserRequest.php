<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        $orgId = $this->route('user')->organisation_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->route('user')->id],
            'password' => ['nullable', Password::defaults()],
            'contracted_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'is_admin' => ['boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('organisation_id', $orgId)],
        ];
    }
}
