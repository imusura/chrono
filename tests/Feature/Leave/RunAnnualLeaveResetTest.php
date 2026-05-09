<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveTransactionType;
use App\Models\LeaveAllocation;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\Organisation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class RunAnnualLeaveResetTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $org;

    private User $user;

    private LeaveType $vacation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LeaveTypeSeeder::class);

        Date::setTestNow(CarbonImmutable::parse('2027-01-01 02:00:00'));

        $this->org = Organisation::factory()->create([
            'year_reset_date'         => '01-01',
            'carryover_max_days'      => 5,
            'carryover_expiry_months' => 3,
            'last_reset_year'         => null,
        ]);

        $this->user = User::factory()->create([
            'organisation_id' => $this->org->id,
            'vacation_days'   => 20,
        ]);

        $this->vacation = LeaveType::where('name', 'Vacation')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    public function test_creates_new_allocation_with_capped_carryover(): void
    {
        LeaveAllocation::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'year'          => 2026,
            'allowance'     => 20,
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -12,
            'date'          => '2026-08-15',
        ]);

        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $newAllocation = LeaveAllocation::where('user_id', $this->user->id)
            ->where('year', 2027)
            ->firstOrFail();

        $this->assertSame(20, $newAllocation->allowance);
        $this->assertSame(5, $newAllocation->carryover_amount);
        $this->assertSame('2027-03-31', $newAllocation->carryover_expires_on->toDateString());
    }

    public function test_posts_carryover_transaction(): void
    {
        LeaveAllocation::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'year'          => 2026,
            'allowance'     => 20,
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -12,
            'date'          => '2026-08-15',
        ]);

        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $carryoverTx = LeaveTransaction::where('user_id', $this->user->id)
            ->where('type', LeaveTransactionType::Usage->value)
            ->whereYear('date', 2027)
            ->count();
        $this->assertSame(0, $carryoverTx);

        $carryoverTx = LeaveTransaction::where('user_id', $this->user->id)
            ->where('type', LeaveTransactionType::Carryover->value)
            ->whereYear('date', 2027)
            ->first();

        $this->assertNotNull($carryoverTx);
        $this->assertEquals(5.0, (float) $carryoverTx->amount);
    }

    public function test_updates_org_last_reset_year(): void
    {
        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $this->org->refresh();
        $this->assertSame(2027, $this->org->last_reset_year);
    }

    public function test_idempotent_on_repeat_run(): void
    {
        LeaveAllocation::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'year'          => 2026,
            'allowance'     => 20,
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -12,
            'date'          => '2026-08-15',
        ]);

        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();
        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $count = LeaveTransaction::where('user_id', $this->user->id)
            ->where('type', LeaveTransactionType::Carryover->value)
            ->whereYear('date', 2027)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_skips_org_when_date_does_not_match(): void
    {
        Date::setTestNow(CarbonImmutable::parse('2027-06-15 02:00:00'));

        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $this->assertDatabaseMissing('leave_allocations', [
            'user_id' => $this->user->id,
            'year'    => 2027,
        ]);
    }

    public function test_does_not_carry_over_negative_balance(): void
    {
        LeaveAllocation::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'year'          => 2026,
            'allowance'     => 20,
        ]);

        LeaveTransaction::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'type'          => LeaveTransactionType::Usage,
            'amount'        => -25,
            'date'          => '2026-08-15',
        ]);

        $this->artisan('app:run-annual-leave-reset')->assertSuccessful();

        $newAllocation = LeaveAllocation::where('user_id', $this->user->id)
            ->where('year', 2027)
            ->firstOrFail();

        $this->assertSame(0, $newAllocation->carryover_amount);
    }
}
