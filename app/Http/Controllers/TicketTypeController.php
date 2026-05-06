<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TicketTypeResource;
use App\Models\Project;
use App\Models\TicketType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketTypeController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $types = TicketType::where('project_id', $project->id)
            ->where('is_active', true)
            ->with('statuses')
            ->get();

        return TicketTypeResource::collection($types);
    }

    public function show(Project $project, TicketType $ticketType): TicketTypeResource
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $ticketType->load(['statuses', 'fields' => fn ($q) => $q->where('is_active', true)]);

        return new TicketTypeResource($ticketType);
    }
}
