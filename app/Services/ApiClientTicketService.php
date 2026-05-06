<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiClient;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketType;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApiClientTicketService
{
    /**
     * @param  array{title: string, content: string, submitter_email?: ?string, submitter_name?: ?string, metadata?: ?array<string, mixed>}  $data
     */
    public function createOrReplay(ApiClient $apiClient, array $data, ?string $idempotencyKey): Ticket
    {
        if ($idempotencyKey !== null) {
            $existing = Ticket::where('created_via_api_client_id', $apiClient->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        try {
            return $this->create($apiClient, $data, $idempotencyKey);
        } catch (UniqueConstraintViolationException|QueryException $e) {
            // Race: another request with the same idempotency key beat us.
            if ($idempotencyKey !== null) {
                $existing = Ticket::where('created_via_api_client_id', $apiClient->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    /**
     * @param  array{title: string, content: string, submitter_email?: ?string, submitter_name?: ?string, metadata?: ?array<string, mixed>}  $data
     */
    private function create(ApiClient $apiClient, array $data, ?string $idempotencyKey): Ticket
    {
        return DB::transaction(function () use ($apiClient, $data, $idempotencyKey): Ticket {
            $type = $apiClient->defaultTicketType()->with('statuses')->first();

            if (! $type instanceof TicketType) {
                throw new RuntimeException('API client default ticket type is missing.');
            }

            $status = $type->firstStatus();

            if ($status === null) {
                throw new RuntimeException('Default ticket type has no statuses configured.');
            }

            $project = Project::whereKey($apiClient->project_id)->lockForUpdate()->firstOrFail();

            $number = $project->next_ticket_number;
            $referenceId = "{$project->prefix}-{$number}";

            $ticket = Ticket::create([
                'project_id' => $project->id,
                'number' => $number,
                'reference_id' => $referenceId,
                'title' => $data['title'],
                'content' => $data['content'],
                'type_id' => $type->id,
                'status_id' => $status->id,
                'priority' => 'medium',
                'created_by' => null,
                'assigned_to' => $project->default_assignee_id,
                'submitter_email' => $data['submitter_email'] ?? null,
                'submitter_name' => $data['submitter_name'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_via_api_client_id' => $apiClient->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $project->increment('next_ticket_number');

            TicketActivity::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'field' => 'created',
                'new_value' => $apiClient->name,
            ]);

            return $ticket;
        });
    }
}
