<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->route('user')->id],
            'password' => ['nullable', Password::defaults()],
            'contracted_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'is_admin' => ['boolean'],
        ];
    }
}
