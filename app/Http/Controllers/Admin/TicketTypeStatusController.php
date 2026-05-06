<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncTicketTypeStatusesRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;

class TicketTypeStatusController extends Controller
{
    public function update(SyncTicketTypeStatusesRequest $request, Project $project, TicketType $ticketType): TicketTypeResource|JsonResponse
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $newStatusIds = collect($request->validated('statuses'))->pluck('status_id');
        $currentStatusIds = $ticketType->statuses()->pluck('ticket_statuses.id');
        $removedStatusIds = $currentStatusIds->diff($newStatusIds);

        if ($removedStatusIds->isNotEmpty()) {
            $ticketsInRemoved = $ticketType->tickets()
                ->whereIn('status_id', $removedStatusIds)
                ->count();

            if ($ticketsInRemoved > 0) {
                $removedNames = TicketStatus::whereIn('id', $removedStatusIds)
                    ->pluck('name')
                    ->join(', ');

                return response()->json([
                    'message' => "{$ticketsInRemoved} ticket(s) are currently in a status being removed ({$removedNames}). Move them to another status first.",
                ], 422);
            }
        }

        $syncData = collect($request->validated('statuses'))
            ->mapWithKeys(fn (array $item) => [$item['status_id'] => [
                'sort_order' => $item['sort_order'],
                'is_final' => $item['is_final'] ?? false,
            ]])
            ->all();

        $ticketType->statuses()->sync($syncData);
        $ticketType->load('statuses');

        return new TicketTypeResource($ticketType);
    }
}
