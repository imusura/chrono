<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organisation_id', 'year', 'month'])]
class PeriodLock extends Model
{
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
