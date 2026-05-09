<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'has_allocation', 'requires_approval', 'allow_carryover'])]
class LeaveType extends Model
{
    protected function casts(): array
    {
        return [
            'has_allocation'    => 'boolean',
            'requires_approval' => 'boolean',
            'allow_carryover'   => 'boolean',
        ];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveTransaction::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
