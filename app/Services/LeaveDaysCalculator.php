<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Models\NonWorkingDay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

class LeaveDaysCalculator
{
    public function daysBetween(CarbonImmutable $start, CarbonImmutable $end, User $user): float
    {
        if ($end->lessThan($start)) {
            return 0.0;
        }

        $nonWorkingSet = $this->nonWorkingDaysIn($user, $start, $end);

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($day->isWeekend()) {
                continue;
            }
            if (isset($nonWorkingSet[$day->toDateString()])) {
                continue;
            }
            $count++;
        }

        return (float) $count;
    }

    /**
     * Map of working dates within [start, end] that the user is on leave,
     * keyed by ISO date, valued by leave type name. Skips weekends and
     * non-working days.
     *
     * @return array<string, string>
     */
    public function expandedLeaveDates(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        if ($end->lessThan($start)) {
            return [];
        }

        $nonWorkingSet = $this->nonWorkingDaysIn($user, $start, $end);
        $days = [];

        $approvedRequests = $user->leaveRequests()
            ->where('status', LeaveRequestStatus::Approved)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->with('leaveType:id,name')
            ->get();

        foreach ($approvedRequests as $req) {
            $cursor = CarbonImmutable::parse($req->start_date)->max($start);
            $until = CarbonImmutable::parse($req->end_date)->min($end);
            while ($cursor->lte($until)) {
                $dateStr = $cursor->toDateString();
                if (! $cursor->isWeekend() && ! isset($nonWorkingSet[$dateStr])) {
                    $days[$dateStr] = $req->leaveType->name;
                }
                $cursor = $cursor->addDay();
            }
        }

        $soloUsage = $user->leaveTransactions()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('type', LeaveTransactionType::Usage)
            ->whereNull('leave_request_id')
            ->with('leaveType:id,name')
            ->get();

        foreach ($soloUsage as $tx) {
            $days[$tx->date->toDateString()] = $tx->leaveType->name;
        }

        return $days;
    }

    /**
     * Map of non-working dates within [start, end] visible to the user
     * (their organisation's custom days plus their country's public holidays),
     * keyed by ISO date, valued by the day's name.
     *
     * @return array<string, string>
     */
    public function nonWorkingDaysIn(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $org = $user->organisation;
        $orgId = $org?->id;
        $countryCode = $org?->country_code;

        return NonWorkingDay::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($orgId, $countryCode) {
                $q->where('organisation_id', $orgId);
                if ($countryCode !== null) {
                    $q->orWhere(fn ($sub) => $sub->whereNull('organisation_id')->where('country_code', $countryCode));
                }
            })
            ->get(['date', 'name'])
            ->mapWithKeys(fn ($d) => [$d->date->toDateString() => $d->name])
            ->all();
    }

}
