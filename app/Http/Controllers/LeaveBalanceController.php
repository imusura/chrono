<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(private readonly LeaveBalanceService $balanceService) {}

    public function index(Request $request): JsonResponse
    {
        $year = $request->integer('year') ?: (int) now()->format('Y');
        $user = $request->user();

        return response()->json([
            'data' => $this->balancesFor($user, $year),
            'year' => $year,
        ]);
    }

    public function showForUser(Request $request, User $user): JsonResponse
    {
        if ($user->organisation_id !== $request->user()->organisation_id) {
            abort(404);
        }

        $year = $request->integer('year') ?: (int) now()->format('Y');

        return response()->json([
            'data'    => $this->balancesFor($user, $year),
            'year'    => $year,
            'user_id' => $user->id,
        ]);
    }

    private function balancesFor(User $user, int $year): array
    {
        return LeaveType::query()
            ->where('has_allocation', true)
            ->orderBy('id')
            ->get()
            ->map(fn (LeaveType $type) => [
                'leave_type_id'        => $type->id,
                'leave_type_name'      => $type->name,
                'balance'              => $this->balanceService->currentBalance($user, $type, $year),
                'unexpired_carryover'  => $this->balanceService->unexpiredCarryover($user, $type),
            ])
            ->all();
    }
}
