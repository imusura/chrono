<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ApiClientWithTokenResource extends ApiClientResource
{
    public function __construct($resource, private readonly string $plainToken)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'token' => $this->plainToken,
        ];
    }
}
