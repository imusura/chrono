<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketWorkflowService
{
    public function canTransition(Ticket $ticket, int $newStatusId, User $user): bool
    {
        if ($ticket->status_id === $newStatusId) {
            return true;
        }

        $typeStatuses = $ticket->ticketType->statuses()->get();

        return $typeStatuses->contains('id', $newStatusId);
    }

    public function getAvailableStatuses(Ticket $ticket, User $user): Collection
    {
        return $ticket->ticketType->statuses()->get()->map(fn (TicketStatus $status) => [
            'status' => $status,
            'allowed' => true,
        ]);
    }

    public function resetStatusForType(int $newTypeId): ?int
    {
        $type = TicketType::find($newTypeId);

        return $type?->firstStatus()?->id;
    }
}
