<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\StoreLeaveAdjustmentRequest;
use App\Http\Resources\LeaveTransactionResource;
use App\Models\User;
use App\Services\LeaveTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveTransactionController extends Controller
{
    public function __construct(private readonly LeaveTransactionService $transactionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $year = $request->integer('year') ?: (int) now()->format('Y');

        $transactions = $request->user()
            ->leaveTransactions()
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return LeaveTransactionResource::collection($transactions);
    }

    public function indexForUser(Request $request, User $user): AnonymousResourceCollection
    {
        if ($user->organisation_id !== $request->user()->organisation_id) {
            abort(404);
        }

        $year = $request->integer('year') ?: (int) now()->format('Y');

        $transactions = $user->leaveTransactions()
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return LeaveTransactionResource::collection($transactions);
    }

    public function storeAdjustment(StoreLeaveAdjustmentRequest $request): LeaveTransactionResource
    {
        $data = $request->validated();

        $user = User::findOrFail($data['user_id']);
        $leaveType = \App\Models\LeaveType::findOrFail($data['leave_type_id']);

        $tx = $this->transactionService->postAdjustment(
            $user,
            $leaveType,
            (float) $data['amount'],
            CarbonImmutable::parse($data['date']),
            $data['note'],
        );

        return new LeaveTransactionResource($tx);
    }
}
