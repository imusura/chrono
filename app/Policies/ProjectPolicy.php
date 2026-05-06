<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function manageProjectConfig(User $user, Project $project): bool
    {
        $role = $user->roleInProject($project);

        return $role === ProjectRole::Admin;
    }

    public function delete(User $user, Project $project): bool
    {
        $role = $user->roleInProject($project);

        return $role === ProjectRole::Admin;
    }
}
