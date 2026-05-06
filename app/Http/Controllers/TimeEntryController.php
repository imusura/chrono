<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntry\StoreTimeEntryRequest;
use App\Http\Requests\TimeEntry\UpdateTimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeEntryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $entries = $request->user()
            ->timeEntries()
            ->with('activity')
            ->whereYear('date', $request->integer('year'))
            ->whereMonth('date', $request->integer('month'))
            ->orderBy('date')
            ->orderBy('started_at')
            ->get();

        return TimeEntryResource::collection($entries);
    }

    public function store(StoreTimeEntryRequest $request): TimeEntryResource
    {
        $entry = $request->user()->timeEntries()->create($request->validated());
        $entry->load('activity');

        return new TimeEntryResource($entry);
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry): TimeEntryResource
    {
        $this->authorize('update', $timeEntry);

        $timeEntry->update($request->validated());
        $timeEntry->load('activity');

        return new TimeEntryResource($timeEntry);
    }

    public function destroy(TimeEntry $timeEntry): JsonResponse
    {
        $this->authorize('update', $timeEntry);

        $timeEntry->delete();

        return response()->json(null, 204);
    }
}
