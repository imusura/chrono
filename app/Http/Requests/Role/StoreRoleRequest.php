<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'organisation_id' => ['nullable', 'integer', 'exists:organisations,id'],
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:50'],
        ];
    }
}
