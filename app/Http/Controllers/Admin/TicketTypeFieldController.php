<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderTicketTypeFieldsRequest;
use App\Http\Requests\Admin\StoreTicketTypeFieldRequest;
use App\Http\Requests\Admin\UpdateTicketTypeFieldRequest;
use App\Http\Resources\TicketTypeFieldResource;
use App\Models\Project;
use App\Models\TicketType;
use App\Models\TicketTypeField;
use Illuminate\Http\JsonResponse;

class TicketTypeFieldController extends Controller
{
    public function store(StoreTicketTypeFieldRequest $request, Project $project, TicketType $ticketType): JsonResponse
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $data = $request->validated();
        $data['sort_order'] = $ticketType->fields()->max('sort_order') + 1;

        $field = $ticketType->fields()->create($data);

        return response()->json(new TicketTypeFieldResource($field), 201);
    }

    public function update(UpdateTicketTypeFieldRequest $request, Project $project, TicketType $ticketType, TicketTypeField $field): TicketTypeFieldResource
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $field->update($request->validated());

        return new TicketTypeFieldResource($field);
    }

    public function destroy(Project $project, TicketType $ticketType, TicketTypeField $field): JsonResponse
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        $field->delete();

        return response()->json(['message' => 'Field deleted.']);
    }

    public function reorder(ReorderTicketTypeFieldsRequest $request, Project $project, TicketType $ticketType): JsonResponse
    {
        abort_unless($ticketType->project_id === $project->id, 404);

        foreach ($request->validated('fields') as $item) {
            TicketTypeField::where('id', $item['id'])
                ->where('ticket_type_id', $ticketType->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Fields reordered.']);
    }
}
