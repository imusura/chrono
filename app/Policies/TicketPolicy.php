<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        $role = $user->roleInProject($ticket->project);

        if (! $role) {
            return false;
        }

        return match ($role) {
            ProjectRole::Admin, ProjectRole::Agent => true,
            ProjectRole::Client => $ticket->created_by === $user->id,
        };
    }

    public function create(User $user, Project $project): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        $role = $user->roleInProject($ticket->project);

        if (! $role) {
            return false;
        }

        return match ($role) {
            ProjectRole::Admin, ProjectRole::Agent => true,
            ProjectRole::Client => $ticket->created_by === $user->id,
        };
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        $role = $user->roleInProject($ticket->project);

        return $role === ProjectRole::Admin;
    }

    public function assign(User $user, Project $project): bool
    {
        $role = $user->roleInProject($project);

        return in_array($role, [ProjectRole::Admin, ProjectRole::Agent]);
    }

    public function manageAttachments(User $user, Ticket $ticket): bool
    {
        $role = $user->roleInProject($ticket->project);

        if (! $role) {
            return false;
        }

        return match ($role) {
            ProjectRole::Admin, ProjectRole::Agent => true,
            ProjectRole::Client => $ticket->created_by === $user->id,
        };
    }
}
