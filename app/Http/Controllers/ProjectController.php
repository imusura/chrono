<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\Project\AddMemberRequest;
use App\Http\Requests\Project\DeleteProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateMemberRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectSetupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectSetupService $setup) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $request->user()->projects()->withCount([
            'tickets',
            'tickets as closed_tickets_count' => fn ($q) => $q->whereNotNull('closed_at'),
            'tickets as active_tickets_count' => fn ($q) => $q->whereNull('closed_at')->whereNotNull('assigned_to'),
            'members',
        ])->get();

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->is_super_admin && ! $user->can_create_projects) {
            abort(403, 'You do not have permission to create projects.');
        }

        $project = Project::create($request->validated());

        $project->members()->attach($user->id, ['role' => ProjectRole::Admin->value]);

        $this->setup->seedFromTemplate($project, $request->validated('template') ?? 'software');

        return response()->json(new ProjectResource($project), 201);
    }

    public function templates(): JsonResponse
    {
        return response()->json(ProjectSetupService::templateOptions());
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project->update($request->validated());

        return new ProjectResource($project);
    }

    public function destroy(DeleteProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }

    public function members(Project $project): AnonymousResourceCollection
    {
        return UserResource::collection($project->members);
    }

    public function addMember(AddMemberRequest $request, Project $project): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $project->members()->attach($user->id, [
            'role' => $request->validated('role'),
        ]);

        return response()->json(['message' => 'Member added.'], 201);
    }

    public function updateMember(UpdateMemberRequest $request, Project $project, User $user): JsonResponse
    {
        $project->members()->updateExistingPivot($user->id, [
            'role' => $request->validated('role'),
        ]);

        if ($request->validated('role') === ProjectRole::Client->value && $project->default_assignee_id === $user->id) {
            $project->update(['default_assignee_id' => null]);
        }

        return response()->json(['message' => 'Member role updated.']);
    }

    public function removeMember(Project $project, User $user): JsonResponse
    {
        $project->members()->detach($user->id);

        if ($project->default_assignee_id === $user->id) {
            $project->update(['default_assignee_id' => null]);
        }

        return response()->json(['message' => 'Member removed.']);
    }
}
