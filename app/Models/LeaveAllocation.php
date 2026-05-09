<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'leave_type_id', 'year', 'allowance', 'carryover_amount', 'carryover_expires_on'])]
class LeaveAllocation extends Model
{
    protected function casts(): array
    {
        return [
            'year'                 => 'integer',
            'allowance'            => 'integer',
            'carryover_amount'     => 'integer',
            'carryover_expires_on' => 'date',
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
}
