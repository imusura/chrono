<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\StoreTicketAttachmentRequest;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreTicketAttachmentRequest $request, Project $project, Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('manageAttachments', $ticket);

        $file = $request->file('file');
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk('local')->putFileAs("attachments/{$ticket->id}", $file, $storedName);

        $attachment = $ticket->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'field' => 'attachment',
            'old_value' => null,
            'new_value' => $file->getClientOriginalName(),
        ]);

        $attachment->load('user');

        return response()->json(new TicketAttachmentResource($attachment), 201);
    }

    public function show(Request $request, Project $project, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('view', $ticket);

        abort_unless($attachment->ticket_id === $ticket->id, 404);

        if ($request->query('preview') && str_starts_with($attachment->mime_type, 'image/')) {
            return Storage::disk('local')->response($attachment->storagePath());
        }

        return Storage::disk('local')->download($attachment->storagePath(), $attachment->original_name);
    }

    public function destroy(Request $request, Project $project, Ticket $ticket, TicketAttachment $attachment): JsonResponse
    {
        abort_unless($ticket->project_id === $project->id, 404);
        $this->authorize('manageAttachments', $ticket);

        abort_unless($attachment->ticket_id === $ticket->id, 404);

        Storage::disk('local')->delete($attachment->storagePath());

        TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'field' => 'attachment',
            'old_value' => $attachment->original_name,
            'new_value' => null,
        ]);

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted.']);
    }
}
