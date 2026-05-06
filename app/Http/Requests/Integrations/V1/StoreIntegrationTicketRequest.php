<?php

declare(strict_types=1);

namespace App\Http\Requests\Integrations\V1;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'submitter_email' => ['nullable', 'email', 'max:255'],
            'submitter_name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array', 'max:32', $this->validateMetadata()],
            'metadata.*' => ['nullable'],
        ];
    }

    private function validateMetadata(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            foreach ($value as $key => $entry) {
                if (! is_string($key)) {
                    $fail('metadata keys must be strings.');

                    return;
                }

                if (! is_scalar($entry) && ! is_null($entry)) {
                    $fail("metadata.{$key} must be a scalar value.");

                    return;
                }

                if (is_string($entry) && strlen($entry) > 1024) {
                    $fail("metadata.{$key} must not exceed 1KB.");

                    return;
                }
            }

            $serialized = json_encode($value);

            if ($serialized === false || strlen($serialized) > 4096) {
                $fail('metadata serialized size must not exceed 4KB.');
            }
        };
    }
}
