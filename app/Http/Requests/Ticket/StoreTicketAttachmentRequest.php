<?php

declare(strict_types=1);

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,log,zip,gz',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('file') && (int) $this->server('CONTENT_LENGTH', 0) > 0) {
            $maxSize = strtoupper(ini_get('upload_max_filesize') ?: '2M');

            abort(413, "The file is too large. The server allows a maximum of {$maxSize}.");
        }
    }
}
