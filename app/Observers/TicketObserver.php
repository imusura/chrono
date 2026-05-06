<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketTypeField;
use App\Models\User;

class TicketObserver
{
    private const TRACKED_FIELDS = ['type_id', 'status_id', 'priority', 'assigned_to', 'title', 'content'];

    public function updating(Ticket $ticket): void
    {
        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        foreach (self::TRACKED_FIELDS as $field) {
            if (! $ticket->isDirty($field)) {
                continue;
            }

            $oldRaw = $ticket->getOriginal($field);
            $newRaw = $ticket->getAttribute($field);

            if ($field === 'content') {
                $this->recordActivity($ticket, $userId, $field, null, null);

                continue;
            }

            if ($field === 'assigned_to') {
                $oldName = $oldRaw ? User::find($oldRaw)?->name : null;
                $newName = $newRaw ? User::find($newRaw)?->name : null;
                $this->recordActivity($ticket, $userId, $field, $oldName, $newName);

                continue;
            }

            if ($field === 'status_id') {
                $oldName = $oldRaw ? TicketStatus::find($oldRaw)?->name : null;
                $newName = $newRaw ? TicketStatus::find($newRaw)?->name : null;
                $this->recordActivity($ticket, $userId, 'status', $oldName, $newName);

                continue;
            }

            if ($field === 'type_id') {
                $oldName = $oldRaw ? TicketType::find($oldRaw)?->name : null;
                $newName = $newRaw ? TicketType::find($newRaw)?->name : null;
                $this->recordActivity($ticket, $userId, 'type', $oldName, $newName);

                continue;
            }

            $oldValue = $oldRaw instanceof \BackedEnum ? $oldRaw->value : (string) $oldRaw;
            $newValue = $newRaw instanceof \BackedEnum ? $newRaw->value : (string) $newRaw;

            $this->recordActivity($ticket, $userId, $field, $oldValue, $newValue);
        }

        if ($ticket->isDirty('custom_fields')) {
            $old = $ticket->getOriginal('custom_fields') ?? [];
            $new = $ticket->custom_fields ?? [];

            $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

            foreach ($allKeys as $fieldId) {
                $oldVal = $old[$fieldId] ?? null;
                $newVal = $new[$fieldId] ?? null;

                if ((string) $oldVal === (string) $newVal) {
                    continue;
                }

                $this->recordActivity(
                    $ticket,
                    $userId,
                    "custom_field:{$fieldId}",
                    $oldVal !== null ? (string) $oldVal : null,
                    $newVal !== null ? (string) $newVal : null,
                );
            }
        }
    }

    private function recordActivity(Ticket $ticket, int $userId, string $field, ?string $oldValue, ?string $newValue): void
    {
        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
