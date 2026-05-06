<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TicketStatusResource;
use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketStatusController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        return TicketStatusResource::collection(
            TicketStatus::where('project_id', $project->id)->where('is_active', true)->get()
        );
    }
}
