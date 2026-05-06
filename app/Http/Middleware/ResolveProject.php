<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ProjectRole;
use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveProject
{
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            abort(404, 'Project not found.');
        }

        $user = $request->user();
        $membership = $user->projects()->where('project_id', $project->id)->first();

        if (! $membership && ! $user->is_super_admin) {
            abort(403, 'You are not a member of this project.');
        }

        $role = $membership ? ProjectRole::from($membership->pivot->role) : null;

        $request->attributes->set('project', $project);
        $request->attributes->set('projectRole', $role);

        return $next($request);
    }
}
