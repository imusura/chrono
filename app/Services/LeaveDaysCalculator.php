<?php

namespace App\Services;

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

        $org = $user->organisation;
        $orgId = $org?->id;
        $countryCode = $org?->country_code;

        $nonWorking = NonWorkingDay::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) use ($orgId, $countryCode) {
                $q->where('organisation_id', $orgId);
                if ($countryCode !== null) {
                    $q->orWhere(fn ($sub) => $sub->whereNull('organisation_id')->where('country_code', $countryCode));
                }
            })
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $nonWorkingSet = array_flip($nonWorking);

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
}
