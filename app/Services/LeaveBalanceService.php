<?php

namespace App\Services;

use App\Models\LeaveAllocation;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;

class LeaveBalanceService
{
    public function currentBalance(User $user, LeaveType $leaveType, ?int $year = null): float
    {
        $year ??= (int) now()->format('Y');

        $allocation = LeaveAllocation::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        $allowance = $allocation !== null ? (float) $allocation->allowance : 0.0;

        $transactionSum = (float) LeaveTransaction::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereYear('date', $year)
            ->sum('amount');

        return $allowance + $transactionSum;
    }

    public function unexpiredCarryover(User $user, LeaveType $leaveType, ?CarbonImmutable $asOf = null): float
    {
        $asOf ??= CarbonImmutable::now();
        $year = (int) $asOf->format('Y');

        $allocation = LeaveAllocation::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($allocation === null || $allocation->carryover_amount === 0) {
            return 0.0;
        }

        if ($allocation->carryover_expires_on !== null && $asOf->greaterThan($allocation->carryover_expires_on)) {
            return 0.0;
        }

        $usageSinceYearStart = (float) LeaveTransaction::query()
            ->where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('type', \App\Enums\LeaveTransactionType::Usage)
            ->whereYear('date', $year)
            ->sum('amount');

        $remaining = (float) $allocation->carryover_amount + $usageSinceYearStart;

        return max(0.0, $remaining);
    }
}
