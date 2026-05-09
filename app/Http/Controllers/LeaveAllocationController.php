<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\StoreLeaveAllocationRequest;
use App\Http\Resources\LeaveAllocationResource;
use App\Models\LeaveAllocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveAllocationController extends Controller
{
    public function indexForUser(Request $request, User $user): AnonymousResourceCollection
    {
        if ($user->organisation_id !== $request->user()->organisation_id) {
            abort(404);
        }

        $allocations = $user->leaveAllocations()
            ->orderBy('year', 'desc')
            ->orderBy('leave_type_id')
            ->get();

        return LeaveAllocationResource::collection($allocations);
    }

    public function store(StoreLeaveAllocationRequest $request): LeaveAllocationResource
    {
        $data = $request->validated();

        $allocation = LeaveAllocation::updateOrCreate(
            [
                'user_id'       => $data['user_id'],
                'leave_type_id' => $data['leave_type_id'],
                'year'          => $data['year'],
            ],
            [
                'allowance'            => $data['allowance'],
                'carryover_amount'     => $data['carryover_amount'] ?? 0,
                'carryover_expires_on' => $data['carryover_expires_on'] ?? null,
            ],
        );

        return new LeaveAllocationResource($allocation);
    }

    public function destroy(Request $request, LeaveAllocation $leaveAllocation): JsonResponse
    {
        if ($leaveAllocation->user->organisation_id !== $request->user()->organisation_id) {
            abort(404);
        }

        $leaveAllocation->delete();

        return response()->json(null, 204);
    }
}
