<?php

namespace App\Console\Commands;

use App\Models\LeaveAllocation;
use App\Services\LeaveTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:run-carryover-expiry')]
#[Description('Expire any unspent carryover days whose expiry date has arrived')]
class RunCarryoverExpiry extends Command
{
    public function __construct(private readonly LeaveTransactionService $transactionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = CarbonImmutable::now()->startOfDay();

        $allocations = LeaveAllocation::query()
            ->where('carryover_expires_on', $today->toDateString())
            ->where('carryover_amount', '>', 0)
            ->with(['user', 'leaveType'])
            ->get();

        if ($allocations->isEmpty()) {
            $this->info('No allocations due for expiry today.');
            return self::SUCCESS;
        }

        $expired = 0;
        foreach ($allocations as $allocation) {
            $tx = $this->transactionService->postExpiry(
                $allocation->user,
                $allocation->leaveType,
                $allocation,
                $today,
            );

            if ($tx !== null) {
                $expired++;
                $this->info("Expired {$tx->amount} days for user #{$allocation->user_id} ({$allocation->leaveType->name})");
            }
        }

        $this->info("Expired carryover for {$expired} allocation(s).");

        return self::SUCCESS;
    }
}
