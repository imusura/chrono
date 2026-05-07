<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organisation_id', 'invited_by', 'email', 'token', 'role_ids', 'contracted_hours', 'is_admin', 'expires_at', 'accepted_at'])]
class Invitation extends Model
{
    protected function casts(): array
    {
        return [
            'role_ids' => 'array',
            'contracted_hours' => 'decimal:2',
            'is_admin' => 'boolean',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
