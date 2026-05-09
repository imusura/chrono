<?php

namespace App\Models;

use App\Enums\LeaveTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'leave_type_id', 'leave_request_id', 'type', 'amount', 'date', 'note'])]
class LeaveTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'type'   => LeaveTransactionType::class,
            'amount' => 'decimal:2',
            'date'   => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
