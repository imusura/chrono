<?php

namespace App\Http\Controllers;

use App\Models\LeaveAllocation;
use App\Services\LeaveDaysCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YearOverviewController extends Controller
{
    public function __construct(private readonly LeaveDaysCalculator $daysCalculator) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = $request->integer('year');
        $user = $request->user();

        $startOfYear = CarbonImmutable::create($year, 1, 1);
        $endOfYear = CarbonImmutable::create($year, 12, 31);

        $minutesByDate = $user->timeEntries()
            ->whereBetween('date', [$startOfYear->toDateString(), $endOfYear->toDateString()])
            ->select('date', DB::raw('SUM(duration_minutes) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')
            ->mapWithKeys(fn ($v, $k) => [$this->normalizeDateKey($k) => (int) $v])
            ->all();

        $nonWorkingDays = $this->daysCalculator->nonWorkingDaysIn($user, $startOfYear, $endOfYear);
        $leaveByDate = $this->daysCalculator->expandedLeaveDates($user, $startOfYear, $endOfYear);

        $days = [];

        foreach ($minutesByDate as $date => $minutes) {
            $days[$date] = $this->ensureDay($days, $date);
            $days[$date]['minutes'] = $minutes;
        }

        foreach ($leaveByDate as $date => $type) {
            $days[$date] = $this->ensureDay($days, $date);
            $days[$date]['leave'] = $type;
        }

        foreach ($nonWorkingDays as $date => $name) {
            $days[$date] = $this->ensureDay($days, $date);
            $days[$date]['non_working'] = $name;
        }

        return response()->json([
            'year'                => $year,
            'contracted_minutes'  => (int) round($user->contracted_hours * 60),
            'first_activity_date' => $this->firstActivityDate($user),
            'days'                => $days,
        ]);
    }

    private function ensureDay(array $days, string $date): array
    {
        return $days[$date] ?? ['minutes' => 0, 'leave' => null, 'non_working' => null];
    }

    private function normalizeDateKey(mixed $key): string
    {
        if ($key instanceof \DateTimeInterface) {
            return $key->format('Y-m-d');
        }
        $str = (string) $key;
        return strlen($str) > 10 ? substr($str, 0, 10) : $str;
    }

    private function firstActivityDate($user): ?string
    {
        $firstTimeEntry = $user->timeEntries()->min('date');
        $firstAllocation = LeaveAllocation::where('user_id', $user->id)->min('year');

        $candidates = [];
        if ($firstTimeEntry !== null) {
            $candidates[] = $this->normalizeDateKey($firstTimeEntry);
        }
        if ($firstAllocation !== null) {
            $candidates[] = sprintf('%04d-01-01', (int) $firstAllocation);
        }

        return $candidates === [] ? null : min($candidates);
    }
}
