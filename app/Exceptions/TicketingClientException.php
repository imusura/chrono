<?php

namespace App\Exceptions;

use RuntimeException;

class TicketingClientException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $upstreamStatus,
        public readonly array $upstreamBody = [],
    ) {
        parent::__construct($message);
    }
}
