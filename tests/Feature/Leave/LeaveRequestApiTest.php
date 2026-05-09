<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Enums\VacationMode;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\Organisation;
use App\Models\User;
use Database\Seeders\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $org;

    private User $user;

    private User $admin;

    private LeaveType $vacation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LeaveTypeSeeder::class);

        $this->org = Organisation::factory()->create();
        $this->user = User::factory()->create([
            'organisation_id' => $this->org->id,
            'vacation_days'   => 20,
        ]);
        $this->admin = User::factory()->create([
            'organisation_id' => $this->org->id,
            'is_admin'        => true,
        ]);
        $this->vacation = LeaveType::where('name', 'Vacation')->firstOrFail();

        LeaveAllocation::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'year'          => (int) now()->format('Y'),
            'allowance'     => 20,
        ]);
    }

    public function test_user_can_list_leave_types(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/leave/types')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Vacation');
    }

    public function test_user_can_view_their_balance(): void
    {
        $year = (int) now()->format('Y');

        $this->actingAs($this->user)
            ->getJson("/api/leave/balance?year={$year}")
            ->assertOk()
            ->assertJsonPath('year', $year)
            ->assertJsonPath('data.0.balance', 20);
    }

    public function test_simple_mode_request_is_auto_approved_and_posts_usage(): void
    {
        $this->org->update(['vacation_mode' => VacationMode::Simple]);

        $this->actingAs($this->user)
            ->postJson('/api/leave/requests', [
                'leave_type_id' => $this->vacation->id,
                'start_date'    => '2026-06-08',
                'end_date'      => '2026-06-12',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.days_count', 5);

        $this->assertDatabaseHas('leave_transactions', [
            'user_id' => $this->user->id,
            'type'    => LeaveTransactionType::Usage->value,
            'amount'  => -5,
        ]);
    }

    public function test_workflow_mode_request_is_pending(): void
    {
        $this->org->update(['vacation_mode' => VacationMode::Workflow]);

        $this->actingAs($this->user)
            ->postJson('/api/leave/requests', [
                'leave_type_id' => $this->vacation->id,
                'start_date'    => '2026-06-08',
                'end_date'      => '2026-06-12',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseMissing('leave_transactions', [
            'user_id' => $this->user->id,
            'type'    => LeaveTransactionType::Usage->value,
        ]);
    }

    public function test_admin_can_approve_pending_request_and_usage_is_posted(): void
    {
        $this->org->update(['vacation_mode' => VacationMode::Workflow]);

        $request = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/leave/requests/{$request->id}", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', $this->admin->id);

        $this->assertDatabaseHas('leave_transactions', [
            'leave_request_id' => $request->id,
            'type'             => LeaveTransactionType::Usage->value,
            'amount'           => -5,
        ]);
    }

    public function test_non_admin_cannot_approve_request(): void
    {
        $other = User::factory()->create(['organisation_id' => $this->org->id]);

        $request = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($other)
            ->patchJson("/api/leave/requests/{$request->id}", ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_cancelling_approved_request_reverses_usage(): void
    {
        $this->org->update(['vacation_mode' => VacationMode::Simple]);

        $createResp = $this->actingAs($this->user)
            ->postJson('/api/leave/requests', [
                'leave_type_id' => $this->vacation->id,
                'start_date'    => '2026-06-08',
                'end_date'      => '2026-06-12',
            ])
            ->assertCreated();

        $requestId = $createResp->json('data.id');

        $this->actingAs($this->user)
            ->patchJson("/api/leave/requests/{$requestId}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $sum = LeaveTransaction::where('leave_request_id', $requestId)->sum('amount');
        $this->assertEquals(0.0, (float) $sum);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $request = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Rejected,
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/leave/requests/{$request->id}", ['status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_rejection_requires_reason(): void
    {
        $request = LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->patchJson("/api/leave/requests/{$request->id}", ['status' => 'rejected'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rejection_reason');
    }

    public function test_user_can_only_see_their_own_requests(): void
    {
        $other = User::factory()->create(['organisation_id' => $this->org->id]);

        LeaveRequest::create([
            'user_id'       => $other->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-03',
            'days_count'    => 3,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/leave/requests')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_list_all_org_requests(): void
    {
        $other = User::factory()->create(['organisation_id' => $this->org->id]);

        LeaveRequest::create([
            'user_id'       => $other->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-06-08',
            'end_date'      => '2026-06-12',
            'days_count'    => 5,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        LeaveRequest::create([
            'user_id'       => $this->user->id,
            'leave_type_id' => $this->vacation->id,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-03',
            'days_count'    => 3,
            'status'        => LeaveRequestStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/api/leave/requests/all')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
