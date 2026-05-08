<?php

namespace App\Http\Controllers;

use App\Enums\TimeEntryMode;
use App\Http\Requests\TimeEntry\BatchStoreTimeEntryRequest;
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
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
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
        $data = $request->validated();
        $data['duration_minutes'] = $this->resolveDuration($request, $data);

        $entry = $request->user()->timeEntries()->create($data);
        $entry->load('activity');

        return new TimeEntryResource($entry);
    }

    public function update(UpdateTimeEntryRequest $request, TimeEntry $timeEntry): TimeEntryResource
    {
        $this->authorize('update', $timeEntry);

        $data = $request->validated();
        $data['duration_minutes'] = $this->resolveDuration($request, $data);

        $timeEntry->update($data);
        $timeEntry->load('activity');

        return new TimeEntryResource($timeEntry);
    }

    public function batch(BatchStoreTimeEntryRequest $request): AnonymousResourceCollection
    {
        $created = [];

        foreach ($request->validated()['entries'] as $data) {
            $data['duration_minutes'] = $this->resolveDuration($request, $data);
            $entry = $request->user()->timeEntries()->create($data);
            $entry->load('activity');
            $created[] = $entry;
        }

        return TimeEntryResource::collection(collect($created));
    }

    public function destroy(TimeEntry $timeEntry): JsonResponse
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        return response()->json(null, 204);
    }

    private function resolveDuration(Request $request, array $data): int
    {
        $mode = $request->user()->organisation?->time_entry_mode ?? TimeEntryMode::Range;

        if ($mode === TimeEntryMode::Duration) {
            return (int) $data['duration_minutes'];
        }

        [$sh, $sm] = array_map('intval', explode(':', $data['started_at']));
        [$eh, $em] = array_map('intval', explode(':', $data['ended_at']));

        return ($eh * 60 + $em) - ($sh * 60 + $sm);
    }
}
