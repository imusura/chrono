<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Organisation extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function periodLocks(): HasMany
    {
        return $this->hasMany(PeriodLock::class);
    }

    public function periodUnlocks(): HasMany
    {
        return $this->hasMany(PeriodUnlock::class);
    }
}
