<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'token_hash',
        'default_ticket_type_id',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function defaultTicketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'default_ticket_type_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_via_api_client_id');
    }

    /**
     * @return array{plain: string, hash: string}
     */
    public static function generateToken(): array
    {
        $plain = 'tkt_'.Str::random(32);

        return [
            'plain' => $plain,
            'hash' => hash('sha256', $plain),
        ];
    }
}
