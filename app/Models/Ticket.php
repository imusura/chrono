<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['project_id', 'number', 'reference_id', 'title', 'content', 'type_id', 'status_id', 'priority', 'custom_fields', 'created_by', 'assigned_to', 'closed_at', 'submitter_email', 'submitter_name', 'metadata', 'created_via_api_client_id', 'idempotency_key'])]
class Ticket extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Ticket $ticket) {
            $ticket->attachments->each(fn (TicketAttachment $attachment) => Storage::disk('local')->delete($attachment->storagePath()));
            Storage::disk('local')->deleteDirectory("attachments/{$ticket->id}");
        });
    }

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'custom_fields' => 'array',
            'metadata' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'type_id');
    }

    public function ticketStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'created_via_api_client_id')->withTrashed();
    }
}
