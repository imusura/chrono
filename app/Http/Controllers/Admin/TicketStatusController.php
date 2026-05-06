<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTicketStatusRequest;
use App\Http\Requests\Admin\UpdateTicketStatusRequest;
use App\Http\Resources\TicketStatusResource;
use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TicketStatusController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        return TicketStatusResource::collection(
            TicketStatus::where('project_id', $project->id)->get()
        );
    }

    public function store(StoreTicketStatusRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['project_id'] = $project->id;

        $status = TicketStatus::create($data);

        return response()->json(new TicketStatusResource($status), 201);
    }

    public function update(UpdateTicketStatusRequest $request, Project $project, TicketStatus $ticketStatus): TicketStatusResource
    {
        abort_unless($ticketStatus->project_id === $project->id, 404);

        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $ticketStatus->update($data);

        return new TicketStatusResource($ticketStatus);
    }

    public function destroy(Project $project, TicketStatus $ticketStatus): JsonResponse
    {
        abort_unless($ticketStatus->project_id === $project->id, 404);

        if ($ticketStatus->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete a status that has tickets. Deactivate it instead.'], 422);
        }

        if ($ticketStatus->types()->exists()) {
            return response()->json(['message' => 'Cannot delete a status that is part of a workflow. Remove it from all types first.'], 422);
        }

        $ticketStatus->delete();

        return response()->json(['message' => 'Ticket status deleted.']);
    }
}
