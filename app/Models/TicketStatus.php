<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected $fillable = ['project_id', 'name', 'slug', 'color', 'icon', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'ticket_type_statuses')
            ->withPivot('sort_order', 'is_final');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }
}
