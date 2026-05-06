<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\BoardTicketRequest;
use App\Http\Requests\Ticket\IndexTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketType;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly TicketWorkflowService $workflow) {}

    public function index(IndexTicketRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Ticket::class, $project]);

        $role = $request->attributes->get('projectRole');
        $query = Ticket::where('project_id', $project->id)
            ->with(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        $query = match ($role) {
            ProjectRole::Client => $query->where('created_by', $request->user()->id),
            ProjectRole::Agent, ProjectRole::Admin => $query,
            default => $query,
        };

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        } else {
            if ($request->filled('status_id')) {
                $query->where('status_id', $request->integer('status_id'));
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->input('priority'));
            }

            if ($request->filled('type_id')) {
                $query->where('type_id', $request->integer('type_id'));
            }

            if ($request->filled('title')) {
                $query->where('title', 'like', "%{$request->input('title')}%");
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            if ($request->filled('created_by')) {
                $query->where('created_by', $request->integer('created_by'));
            }
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $perPage = $request->integer('per_page', 15);

        return TicketResource::collection($query->paginate($perPage));
    }

    public function store(StoreTicketRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Ticket::class, $project]);

        $type = TicketType::where('project_id', $project->id)->findOrFail($request->validated('type_id'));
        $firstStatus = $type->firstStatus();

        $number = $project->next_ticket_number;
        $referenceId = "{$project->prefix}-{$number}";

        $ticket = Ticket::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'number' => $number,
            'reference_id' => $referenceId,
            'status_id' => $firstStatus->id,
            'created_by' => $request->user()->id,
            'assigned_to' => $project->default_assignee_id,
        ]);

        $project->increment('next_ticket_number');

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'field' => 'created',
        ]);

        $ticket->load(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        return response()->json(new TicketResource($ticket), 201);
    }

    public function show(Request $request, Project $project, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('view', $ticket);

        $ticket->load([
            'creator',
            'assignee',
            'ticketType.statuses',
            'ticketType.fields' => fn ($q) => $q->where('is_active', true),
            'ticketStatus',
            'comments.user',
            'activities.user',
            'attachments.user',
            'apiClient',
        ]);

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Project $project, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('update', $ticket);

        $data = $request->validated();

        $role = $request->user()->roleInProject($project);
        if ($role === ProjectRole::Client && isset($data['type_id'])) {
            abort(403, 'Clients cannot change ticket type.');
        }

        $typeChanged = false;

        if (isset($data['type_id']) && $data['type_id'] !== $ticket->type_id) {
            $data['status_id'] = $this->workflow->resetStatusForType($data['type_id']);
            $typeChanged = true;
        }

        if (! $typeChanged && isset($data['status_id']) && $data['status_id'] !== $ticket->status_id) {
            if (! $this->workflow->canTransition($ticket, $data['status_id'], $request->user())) {
                return abort(422, 'Invalid status transition.');
            }
        }

        $ticket->update($data);
        $ticket->load(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        return new TicketResource($ticket);
    }

    public function destroy(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted.']);
    }

    public function close(Request $request, Project $project, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('update', $ticket);

        $ticket->update(['closed_at' => now()]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'field' => 'closed',
        ]);

        $ticket->load(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        return new TicketResource($ticket);
    }

    public function reopen(Request $request, Project $project, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('update', $ticket);

        $ticket->update(['closed_at' => null]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'field' => 'reopened',
        ]);
        $ticket->load(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        return new TicketResource($ticket);
    }

    public function board(BoardTicketRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Ticket::class, $project]);

        $role = $request->attributes->get('projectRole');
        $query = Ticket::where('project_id', $project->id)
            ->with(['creator', 'assignee', 'ticketType.statuses', 'ticketStatus']);

        $query = match ($role) {
            ProjectRole::Client => $query->where('created_by', $request->user()->id),
            ProjectRole::Agent, ProjectRole::Admin => $query,
            default => $query,
        };

        $query->whereNull('closed_at');

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->integer('created_by'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->integer('type_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        $query->orderBy('updated_at', 'desc');

        return TicketResource::collection($query->get());
    }

    public function assign(AssignTicketRequest $request, Project $project, Ticket $ticket): TicketResource
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('assign', [Ticket::class, $project]);

        $ticket->update($request->validated());
        $ticket->load(['creator', 'assignee', 'ticketType', 'ticketStatus']);

        return new TicketResource($ticket);
    }
}
