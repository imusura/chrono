<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreCommentRequest $request, Project $project, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('view', $ticket);

        $comment = $ticket->comments()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $comment->load('user');

        return response()->json(new CommentResource($comment), 201);
    }
}
