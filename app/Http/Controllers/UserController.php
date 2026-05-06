<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(IndexUserRequest $request, Project $project): AnonymousResourceCollection
    {
        $role = $request->attributes->get('projectRole');

        if ($role === ProjectRole::Client) {
            abort(403);
        }

        $query = $project->members();

        if ($request->filled('role')) {
            $query->wherePivot('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role === ProjectRole::Agent) {
            $query->whereIn('users.id', function ($sub) use ($request, $project) {
                $sub->select('created_by')
                    ->from('tickets')
                    ->where('project_id', $project->id)
                    ->where('assigned_to', $request->user()->id)
                    ->distinct();
            });
        }

        $query->orderBy('name');

        return UserResource::collection($query->get());
    }
}
