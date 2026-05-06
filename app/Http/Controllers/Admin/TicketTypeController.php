<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTicketTypeRequest;
use App\Http\Requests\Admin\UpdateTicketTypeRequest;
use App\Http\Resources\TicketTypeResource;
use App\Models\Project;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TicketTypeController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $types = TicketType::where('project_id', $project->id)->with('statuses')->get();

        return TicketTypeResource::collection($types);
    }

    public function store(StoreTicketTypeRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['project_id'] = $project->id;

        if (array_key_exists('is_default', $data) && $data['is_default']) {
            TicketType::where('project_id', $project->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $type = TicketType::create($data);
        $type->load('statuses');

        return response()->json(new TicketTypeResource($type), 201);
    }

    public function update(UpdateTicketTypeRequest $request, Project $project, TicketType $ticketType): TicketTypeResource
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (array_key_exists('is_default', $data) && $data['is_default']) {
            TicketType::where('project_id', $project->id)->where('is_default', true)->where('id', '!=', $ticketType->id)->update(['is_default' => false]);
        }

        $ticketType->update($data);
        $ticketType->load('statuses');

        return new TicketTypeResource($ticketType);
    }

    public function destroy(Project $project, TicketType $ticketType): JsonResponse
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        if ($ticketType->tickets()->exists()) {
            return response()->json(['message' => 'Cannot delete a type that has tickets. Deactivate it instead.'], 422);
        }

        $ticketType->delete();

        return response()->json(['message' => 'Ticket type deleted.']);
    }
}
