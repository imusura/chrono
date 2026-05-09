<?php

namespace App\Console\Commands;

use App\Models\LeaveAllocation;
use App\Models\LeaveType;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LeaveBalanceService;
use App\Services\LeaveTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:run-annual-leave-reset {--force : Run regardless of date and last_reset_year}')]
#[Description('Annual leave allocation reset — fires when today matches an organisation\'s year_reset_date')]
class RunAnnualLeaveReset extends Command
{
    public function __construct(
        private readonly LeaveBalanceService $balanceService,
        private readonly LeaveTransactionService $transactionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = CarbonImmutable::now();
        $todayMmDd = $today->format('m-d');
        $currentYear = (int) $today->format('Y');
        $force = (bool) $this->option('force');

        $orgs = Organisation::query()
            ->when(! $force, fn ($q) => $q
                ->where('year_reset_date', $todayMmDd)
                ->where(fn ($sub) => $sub->whereNull('last_reset_year')->orWhere('last_reset_year', '<', $currentYear))
            )
            ->get();

        if ($orgs->isEmpty()) {
            $this->info('No organisations due for reset today.');
            return self::SUCCESS;
        }

        $allocatableTypes = LeaveType::query()
            ->where('has_allocation', true)
            ->where('allow_carryover', true)
            ->get();

        foreach ($orgs as $org) {
            $this->info("Processing organisation: {$org->name}");
            DB::transaction(function () use ($org, $allocatableTypes, $currentYear, $today) {
                foreach ($org->users as $user) {
                    foreach ($allocatableTypes as $type) {
                        $this->resetUser($user, $type, $currentYear, $today, $org);
                    }
                }
                $org->update(['last_reset_year' => $currentYear]);
            });
        }

        return self::SUCCESS;
    }

    private function resetUser(User $user, LeaveType $type, int $currentYear, CarbonImmutable $today, Organisation $org): void
    {
        $previousYear = $currentYear - 1;
        $carryoverDays = 0.0;

        if ($type->allow_carryover) {
            $previousBalance = $this->balanceService->currentBalance($user, $type, $previousYear);
            $carryoverDays = max(0.0, $previousBalance);

            if ($org->carryover_max_days !== null) {
                $carryoverDays = min($carryoverDays, (float) $org->carryover_max_days);
            }
        }

        $expiresOn = null;
        if ($carryoverDays > 0 && $org->carryover_expiry_months !== null) {
            $expiresOn = $today->addMonths($org->carryover_expiry_months)->subDay();
        }

        $allowance = (int) $user->vacation_days;

        LeaveAllocation::updateOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $currentYear],
            [
                'allowance'            => $allowance,
                'carryover_amount'     => (int) round($carryoverDays),
                'carryover_expires_on' => $expiresOn,
            ],
        );

        if ($carryoverDays > 0) {
            $this->transactionService->postCarryover($user, $type, $carryoverDays, $today);
        }
    }
}
