<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Enums\VacationMode;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Requests\Leave\UpdateLeaveRequestStatusRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveDaysCalculator;
use App\Services\LeaveTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveDaysCalculator $daysCalculator,
        private readonly LeaveTransactionService $transactionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = $request->user()
            ->leaveRequests()
            ->orderBy('start_date', 'desc')
            ->get();

        return LeaveRequestResource::collection($requests);
    }

    public function days(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = $request->integer('year');
        $month = $request->integer('month');
        $user = $request->user();

        $start = CarbonImmutable::create($year, $month, 1);
        $end = $start->endOfMonth()->startOfDay();

        return response()->json([
            'data' => $this->daysCalculator->expandedLeaveDates($user, $start, $end),
        ]);
    }

    public function indexForOrganisation(Request $request): AnonymousResourceCollection
    {
        $orgUserIds = $request->user()->organisation?->users()->pluck('id') ?? collect();

        $requests = LeaveRequest::query()
            ->whereIn('user_id', $orgUserIds)
            ->orderBy('start_date', 'desc')
            ->get();

        return LeaveRequestResource::collection($requests);
    }

    public function store(StoreLeaveRequestRequest $request): LeaveRequestResource
    {
        $data = $request->validated();
        $user = $request->user();

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        $start = CarbonImmutable::parse($data['start_date']);
        $end = CarbonImmutable::parse($data['end_date']);
        $daysCount = $this->daysCalculator->daysBetween($start, $end, $user);

        if ($daysCount <= 0) {
            throw ValidationException::withMessages([
                'start_date' => __('The selected date range contains no working days.'),
            ]);
        }

        $mode = $user->organisation?->vacation_mode ?? VacationMode::Simple;
        $isSimpleMode = $mode === VacationMode::Simple;
        $requiresApproval = $leaveType->requires_approval || $mode === VacationMode::Workflow;

        $status = $isSimpleMode || ! $requiresApproval
            ? LeaveRequestStatus::Approved
            : LeaveRequestStatus::Pending;

        $leaveRequest = DB::transaction(function () use ($user, $leaveType, $start, $end, $daysCount, $status) {
            $req = LeaveRequest::create([
                'user_id'       => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date'    => $start,
                'end_date'      => $end,
                'days_count'    => $daysCount,
                'status'        => $status,
            ]);

            if ($status === LeaveRequestStatus::Approved && $leaveType->has_allocation) {
                $this->transactionService->postUsage($user, $leaveType, $daysCount, $start, $req);
            }

            return $req;
        });

        return new LeaveRequestResource($leaveRequest);
    }

    public function updateStatus(UpdateLeaveRequestStatusRequest $request, LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $this->authorizeStatusChange($request, $leaveRequest);

        $data = $request->validated();
        $newStatus = LeaveRequestStatus::from($data['status']);

        $this->validateTransition($leaveRequest->status, $newStatus, $request);

        $leaveRequest = DB::transaction(function () use ($leaveRequest, $newStatus, $request, $data) {
            $previousStatus = $leaveRequest->status;
            $leaveType = $leaveRequest->leaveType;

            $leaveRequest->update([
                'status'           => $newStatus,
                'approved_by'      => in_array($newStatus, [LeaveRequestStatus::Approved, LeaveRequestStatus::Rejected], true)
                    ? $request->user()->id
                    : $leaveRequest->approved_by,
                'rejection_reason' => $newStatus === LeaveRequestStatus::Rejected
                    ? ($data['rejection_reason'] ?? null)
                    : null,
            ]);

            if ($newStatus === LeaveRequestStatus::Approved
                && $previousStatus !== LeaveRequestStatus::Approved
                && $leaveType->has_allocation) {
                $this->transactionService->postUsage(
                    $leaveRequest->user,
                    $leaveType,
                    (float) $leaveRequest->days_count,
                    CarbonImmutable::parse($leaveRequest->start_date),
                    $leaveRequest,
                );
            }

            if ($newStatus === LeaveRequestStatus::Cancelled
                && $previousStatus === LeaveRequestStatus::Approved
                && $leaveType->has_allocation) {
                $this->transactionService->reverseUsage($leaveRequest);
            }

            return $leaveRequest->fresh();
        });

        return new LeaveRequestResource($leaveRequest);
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        if ($leaveRequest->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($leaveRequest->status, [LeaveRequestStatus::Draft, LeaveRequestStatus::Pending], true)) {
            throw ValidationException::withMessages([
                'status' => __('Only draft or pending requests can be deleted.'),
            ]);
        }

        $leaveRequest->delete();

        return response()->json(null, 204);
    }

    private function authorizeStatusChange(Request $request, LeaveRequest $leaveRequest): void
    {
        $actor = $request->user();
        $isOwner = $leaveRequest->user_id === $actor->id;
        $isAdmin = (bool) $actor->is_admin;
        $sameOrg = $leaveRequest->user->organisation_id === $actor->organisation_id;

        if (! $isOwner && ! ($isAdmin && $sameOrg)) {
            abort(403);
        }
    }

    private function validateTransition(LeaveRequestStatus $from, LeaveRequestStatus $to, Request $request): void
    {
        $isAdmin = (bool) $request->user()->is_admin;

        $allowed = match ($from) {
            LeaveRequestStatus::Draft     => [LeaveRequestStatus::Pending, LeaveRequestStatus::Cancelled],
            LeaveRequestStatus::Pending   => $isAdmin
                ? [LeaveRequestStatus::Approved, LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled]
                : [LeaveRequestStatus::Cancelled],
            LeaveRequestStatus::Approved  => [LeaveRequestStatus::Cancelled],
            LeaveRequestStatus::Rejected  => [],
            LeaveRequestStatus::Cancelled => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('Invalid status transition from :from to :to.', ['from' => $from->value, 'to' => $to->value]),
            ]);
        }
    }
}
