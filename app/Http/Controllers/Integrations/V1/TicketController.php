<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\V1\StoreIntegrationTicketRequest;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Services\ApiClientTicketService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function store(StoreIntegrationTicketRequest $request, ApiClientTicketService $service): Response
    {
        /** @var ApiClient $apiClient */
        $apiClient = $request->attributes->get('apiClient');

        $idempotencyKey = $this->validIdempotencyKey($request->header('Idempotency-Key'));

        if ($request->headers->has('Idempotency-Key') && $idempotencyKey === null) {
            return response([
                'message' => 'Invalid Idempotency-Key header.',
                'errors' => ['idempotency_key' => ['Must match /^[A-Za-z0-9_-]{8,128}$/.']],
            ], 422);
        }

        $replay = $idempotencyKey !== null && Ticket::where('created_via_api_client_id', $apiClient->id)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        $ticket = $service->createOrReplay($apiClient, [
            'title' => $request->validated('subject'),
            'content' => $request->validated('description'),
            'submitter_email' => $request->validated('submitter_email'),
            'submitter_name' => $request->validated('submitter_name'),
            'metadata' => $request->validated('metadata'),
        ], $idempotencyKey);

        Log::channel('integrations-api')->info('ticket.create', [
            'api_client_id' => $apiClient->id,
            'project_id' => $apiClient->project_id,
            'ticket_id' => $ticket->id,
            'reference_id' => $ticket->reference_id,
            'replay' => $replay,
            'idempotency_key_present' => $idempotencyKey !== null,
        ]);

        return response('', 201);
    }

    private function validIdempotencyKey(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_match('/^[A-Za-z0-9_\-]{8,128}$/', $value) === 1 ? $value : null;
    }
}
