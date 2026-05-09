<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveTransactionType;
use App\Models\LeaveAllocation;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\Organisation;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Carbon\CarbonImmutable;
use Database\Seeders\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $service;

    private User $user;

    private LeaveType $vacation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LeaveTypeSeeder::class);

        $org = Organisation::factory()->create();
        $this->user = User::factory()->create(['organisation_id' => $org->id, 'vacation_days' => 20]);
        $this->vacation = LeaveType::where('name', 'Vacation')->firstOrFail();
        $this->service = app(LeaveBalanceService::class);
    }

    public function test_balance_is_allowance_plus_transactions(): void
    {
        LeaveAllocation::create([
            'user_id'          => $this->user->id,
            'leave_type_id'    => $this->vacation->id,
            'year'             => 2026,
            'allowance'        => 20,
            'carryover_amount' => 3,
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Carryover,
            'amount'        => 3,
            'date'          => '2026-01-01',
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -5,
            'date'          => '2026-07-01',
        ]);

        $balance = $this->service->currentBalance($this->user, $this->vacation, 2026);

        $this->assertSame(18.0, $balance);
    }

    public function test_balance_returns_zero_when_no_allocation_or_transactions(): void
    {
        $balance = $this->service->currentBalance($this->user, $this->vacation, 2026);

        $this->assertSame(0.0, $balance);
    }

    public function test_unexpired_carryover_subtracts_usage(): void
    {
        LeaveAllocation::create([
            'user_id'              => $this->user->id,
            'leave_type_id'        => $this->vacation->id,
            'year'                 => 2026,
            'allowance'            => 20,
            'carryover_amount'     => 5,
            'carryover_expires_on' => '2026-03-31',
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -2,
            'date'          => '2026-02-15',
        ]);

        $remaining = $this->service->unexpiredCarryover(
            $this->user,
            $this->vacation,
            CarbonImmutable::parse('2026-03-15'),
        );

        $this->assertSame(3.0, $remaining);
    }

    public function test_unexpired_carryover_returns_zero_after_expiry_date(): void
    {
        LeaveAllocation::create([
            'user_id'              => $this->user->id,
            'leave_type_id'        => $this->vacation->id,
            'year'                 => 2026,
            'allowance'            => 20,
            'carryover_amount'     => 5,
            'carryover_expires_on' => '2026-03-31',
        ]);

        $remaining = $this->service->unexpiredCarryover(
            $this->user,
            $this->vacation,
            CarbonImmutable::parse('2026-04-01'),
        );

        $this->assertSame(0.0, $remaining);
    }

    public function test_unexpired_carryover_returns_zero_when_fully_used(): void
    {
        LeaveAllocation::create([
            'user_id'              => $this->user->id,
            'leave_type_id'        => $this->vacation->id,
            'year'                 => 2026,
            'allowance'            => 20,
            'carryover_amount'     => 3,
            'carryover_expires_on' => '2026-03-31',
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -10,
            'date'          => '2026-02-15',
        ]);

        $remaining = $this->service->unexpiredCarryover(
            $this->user,
            $this->vacation,
            CarbonImmutable::parse('2026-03-15'),
        );

        $this->assertSame(0.0, $remaining);
    }
}
