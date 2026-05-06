<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    protected $fillable = ['project_id', 'name', 'slug', 'color', 'icon', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(TicketStatus::class, 'ticket_type_statuses')
            ->withPivot('sort_order', 'is_final')
            ->orderByPivot('sort_order');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'type_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(TicketTypeField::class)->orderBy('sort_order');
    }

    public function firstStatus(): ?TicketStatus
    {
        return $this->statuses()->first();
    }
}
