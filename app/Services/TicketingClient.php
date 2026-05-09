<?php

namespace App\Services;

use App\Exceptions\TicketingClientException;
use App\Exceptions\TicketingNotConfiguredException;
use App\Exceptions\TicketingUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TicketingClient
{
    public function createTicket(array $payload, ?string $idempotencyKey = null): void
    {
        $baseUrl = config('services.ticketing.base_url');
        $token = config('services.ticketing.token');

        if (empty($baseUrl) || empty($token)) {
            throw new TicketingNotConfiguredException('Ticketing integration is not configured.');
        }

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $url = rtrim($baseUrl, '/').'/api/integrations/v1/tickets';

        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) config('services.ticketing.timeout', 10))
                ->retry(0)
                ->asJson()
                ->acceptJson()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new TicketingUnavailableException('Could not connect to ticketing service: '.$e->getMessage());
        }

        if ($response->status() >= 500) {
            throw new TicketingUnavailableException('Ticketing service returned '.$response->status());
        }

        if ($response->status() >= 400) {
            throw new TicketingClientException(
                'Ticketing rejected the request.',
                $response->status(),
                $response->json() ?? [],
            );
        }
    }
}
