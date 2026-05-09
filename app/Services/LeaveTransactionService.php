<?php

namespace App\Services;

use App\Enums\LeaveTransactionType;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;

class LeaveTransactionService
{
    public function __construct(private readonly LeaveBalanceService $balanceService) {}

    public function postUsage(
        User $user,
        LeaveType $leaveType,
        float $days,
        CarbonImmutable $date,
        ?LeaveRequest $request = null,
        ?string $note = null,
    ): LeaveTransaction {
        return LeaveTransaction::create([
            'user_id'          => $user->id,
            'leave_type_id'    => $leaveType->id,
            'leave_request_id' => $request?->id,
            'type'             => LeaveTransactionType::Usage,
            'amount'           => -1 * abs($days),
            'date'             => $date,
            'note'             => $note,
        ]);
    }

    public function postAdjustment(
        User $user,
        LeaveType $leaveType,
        float $days,
        CarbonImmutable $date,
        string $note,
    ): LeaveTransaction {
        return LeaveTransaction::create([
            'user_id'       => $user->id,
            'leave_type_id' => $leaveType->id,
            'type'          => LeaveTransactionType::Adjustment,
            'amount'        => $days,
            'date'          => $date,
            'note'          => $note,
        ]);
    }

    public function postCarryover(
        User $user,
        LeaveType $leaveType,
        float $days,
        CarbonImmutable $date,
    ): LeaveTransaction {
        return LeaveTransaction::create([
            'user_id'       => $user->id,
            'leave_type_id' => $leaveType->id,
            'type'          => LeaveTransactionType::Carryover,
            'amount'        => abs($days),
            'date'          => $date,
        ]);
    }

    public function postExpiry(
        User $user,
        LeaveType $leaveType,
        LeaveAllocation $allocation,
        CarbonImmutable $date,
    ): ?LeaveTransaction {
        $remaining = $this->balanceService->unexpiredCarryover($user, $leaveType, $date);

        if ($remaining <= 0) {
            return null;
        }

        return LeaveTransaction::create([
            'user_id'       => $user->id,
            'leave_type_id' => $leaveType->id,
            'type'          => LeaveTransactionType::Expiry,
            'amount'        => -1 * $remaining,
            'date'          => $date,
            'note'          => 'Carryover expired',
        ]);
    }

    public function reverseUsage(LeaveRequest $request, string $note = 'Request cancelled'): LeaveTransaction
    {
        $usage = LeaveTransaction::query()
            ->where('leave_request_id', $request->id)
            ->where('type', LeaveTransactionType::Usage)
            ->first();

        $amount = $usage !== null ? -1 * (float) $usage->amount : (float) $request->days_count;

        return LeaveTransaction::create([
            'user_id'          => $request->user_id,
            'leave_type_id'    => $request->leave_type_id,
            'leave_request_id' => $request->id,
            'type'             => LeaveTransactionType::Adjustment,
            'amount'           => $amount,
            'date'             => CarbonImmutable::now(),
            'note'             => $note,
        ]);
    }
}
