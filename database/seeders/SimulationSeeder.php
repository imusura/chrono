<?php

namespace Database\Seeders;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTransactionType;
use App\Enums\VacationMode;
use App\Models\Activity;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\NonWorkingDay;
use App\Models\Organisation;
use App\Models\PeriodLock;
use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds 20 users with 10 years of realistic time entries (2016-01-01 to 2025-12-31)
 * plus full leave tracking history: annual allocations, carryover, vacations,
 * sick days, and occasional paid leave grants.
 *
 * Run after DatabaseSeeder.
 * Usage: php artisan db:seed --class=SimulationSeeder
 */
class SimulationSeeder extends Seeder
{
    private const START_YEAR = 2016;

    private const END_YEAR = 2025;

    private const NUM_USERS = 20;

    private const CARRYOVER_MAX_DAYS = 5;

    private const CARRYOVER_EXPIRY_MONTHS = 3;

    private const VACATION_DAYS_PER_YEAR = 20;

    private const RECURRING_HOLIDAYS = [
        '01-01', '01-06', '05-01', '06-22',
        '08-05', '08-15', '10-08', '11-01',
        '12-25', '12-26',
    ];

    private const EASTER_RELATIVE = [0, 1, 60];

    private array $activityIdsByName = [];

    private int $vacationTypeId;

    private int $sickTypeId;

    private int $paidLeaveTypeId;

    public function run(): void
    {
        $org = Organisation::first();
        if (! $org) {
            $this->command->error('Run DatabaseSeeder first.');
            return;
        }

        $this->configureOrgForLeaveTracking($org);
        $this->cacheActivityAndLeaveTypeIds($org);
        $this->seedHolidaysForAllYears();

        $users = $this->createOrFindSimulationUsers($org);

        $this->command->info('Wiping existing simulation data...');
        $this->wipeSimulationData($users);

        $this->command->info('Seeding 20 users × 10 years of data...');
        foreach ($users as $userData) {
            $this->seedUserHistory($userData);
        }

        $this->command->info('Locking historical periods (2016-01 through 2025-12)...');
        $this->lockPeriods($org->id);

        $org->update(['last_reset_year' => 2026]);

        $this->command->info('Done. Simulation users: sim1@chrono.test … sim20@chrono.test, password: password');
    }

    private function configureOrgForLeaveTracking(Organisation $org): void
    {
        $org->update([
            'vacation_mode'           => VacationMode::Simple->value,
            'year_reset_date'         => '01-01',
            'carryover_max_days'      => self::CARRYOVER_MAX_DAYS,
            'carryover_expiry_months' => self::CARRYOVER_EXPIRY_MONTHS,
        ]);
    }

    private function cacheActivityAndLeaveTypeIds(Organisation $org): void
    {
        $this->activityIdsByName = Activity::where('organisation_id', $org->id)
            ->pluck('id', 'name')
            ->all();

        $this->vacationTypeId = LeaveType::where('name', 'Vacation')->value('id');
        $this->sickTypeId = LeaveType::where('name', 'Sick Day')->value('id');
        $this->paidLeaveTypeId = LeaveType::where('name', 'Paid Leave')->value('id');
    }

    private function seedHolidaysForAllYears(): void
    {
        $rows = [];
        $now = now();

        for ($year = self::START_YEAR; $year <= 2026; $year++) {
            foreach (self::RECURRING_HOLIDAYS as $md) {
                $rows[] = [
                    'organisation_id' => null,
                    'country_code'    => 'HR',
                    'date'            => "$year-$md",
                    'name'            => 'Public Holiday',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            $easter = $this->easterDate($year);
            foreach (self::EASTER_RELATIVE as $offset) {
                $rows[] = [
                    'organisation_id' => null,
                    'country_code'    => 'HR',
                    'date'            => $easter->copy()->addDays($offset)->toDateString(),
                    'name'            => 'Easter-related Holiday',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        foreach ($rows as $row) {
            NonWorkingDay::firstOrCreate(
                [
                    'organisation_id' => null,
                    'country_code'    => $row['country_code'],
                    'date'            => $row['date'],
                ],
                ['name' => $row['name']],
            );
        }
    }

    /**
     * @return array<int, array{user: User, hireDate: Carbon, role: string}>
     */
    private function createOrFindSimulationUsers(Organisation $org): array
    {
        $devRole = Role::where('organisation_id', $org->id)->where('name', 'Developer')->firstOrFail();
        $desRole = Role::where('organisation_id', $org->id)->where('name', 'Designer')->firstOrFail();
        $mgrRole = Role::where('organisation_id', $org->id)->where('name', 'Manager')->firstOrFail();

        $defs = [
            ['Ana Horvat',     'developer', $devRole, 2016],
            ['Marko Kovac',    'developer', $devRole, 2016],
            ['Iva Novak',      'developer', $devRole, 2017],
            ['Luka Babic',     'developer', $devRole, 2018],
            ['Mia Jurcevic',   'developer', $devRole, 2019],
            ['Petar Vukovic',  'developer', $devRole, 2020],
            ['Sara Maric',     'developer', $devRole, 2021],
            ['Filip Tomic',    'developer', $devRole, 2023],
            ['Lara Pavlovic',  'designer',  $desRole, 2016],
            ['Ivan Saric',     'designer',  $desRole, 2017],
            ['Nina Knezevic',  'designer',  $desRole, 2019],
            ['Tomislav Bozic', 'designer',  $desRole, 2021],
            ['Dora Kraljevic', 'designer',  $desRole, 2022],
            ['Karlo Matic',    'manager',   $mgrRole, 2016],
            ['Jana Vidovic',   'manager',   $mgrRole, 2016],
            ['Stjepan Lukic',  'manager',   $mgrRole, 2017],
            ['Rea Brkic',      'manager',   $mgrRole, 2018],
            ['Damir Cvitan',   'manager',   $mgrRole, 2019],
            ['Vedrana Galic',  'manager',   $mgrRole, 2020],
            ['Borna Soldo',    'manager',   $mgrRole, 2022],
        ];

        $result = [];
        foreach ($defs as $i => [$name, $role, $roleModel, $hireYear]) {
            $email = 'sim' . ($i + 1) . '@chrono.test';
            $hireDate = $this->deterministicHireDate($email, $hireYear);

            $user = User::firstOrCreate(['email' => $email], [
                'name'             => $name,
                'password'         => Hash::make('password'),
                'organisation_id'  => $org->id,
                'contracted_hours' => 8.00,
                'vacation_days'    => self::VACATION_DAYS_PER_YEAR,
            ]);

            if (! $user->roles()->where('role_id', $roleModel->id)->exists()) {
                $user->roles()->attach($roleModel->id);
            }

            $result[] = ['user' => $user, 'hireDate' => $hireDate, 'role' => $role];
        }

        return $result;
    }

    private function deterministicHireDate(string $email, int $year): Carbon
    {
        $month = 1 + (abs(crc32($email . 'hireMonth')) % 12);
        $day = 1 + (abs(crc32($email . 'hireDay')) % 28);
        return Carbon::create($year, $month, $day);
    }

    /**
     * @param  array<int, array{user: User, hireDate: Carbon, role: string}>  $users
     */
    private function wipeSimulationData(array $users): void
    {
        $userIds = array_map(fn ($u) => $u['user']->id, $users);

        DB::transaction(function () use ($userIds) {
            LeaveTransaction::whereIn('user_id', $userIds)->delete();
            LeaveRequest::whereIn('user_id', $userIds)->delete();
            LeaveAllocation::whereIn('user_id', $userIds)->delete();
            TimeEntry::whereIn('user_id', $userIds)->delete();
        });
    }

    /**
     * @param  array{user: User, hireDate: Carbon, role: string}  $userData
     */
    private function seedUserHistory(array $userData): void
    {
        $user = $userData['user'];
        $hireDate = $userData['hireDate'];
        $role = $userData['role'];

        $this->command->info("  → {$user->name} ({$role}, hired {$hireDate->toDateString()})");

        $previousYearBalance = 0;

        for ($year = self::START_YEAR; $year <= self::END_YEAR; $year++) {
            if ($hireDate->year > $year) {
                continue;
            }

            $isFirstYear = ($year === $hireDate->year && $hireDate->month > 1);
            $allowance = $isFirstYear
                ? max(5, (int) round(self::VACATION_DAYS_PER_YEAR * (12 - $hireDate->month + 1) / 12))
                : self::VACATION_DAYS_PER_YEAR;

            $carryover = min((int) round($previousYearBalance), self::CARRYOVER_MAX_DAYS);

            $allocation = $this->createAllocation($user, $year, $allowance, $carryover, $hireDate, $isFirstYear);

            if ($carryover > 0) {
                $this->postLeaveTransaction(
                    $user->id,
                    $this->vacationTypeId,
                    LeaveTransactionType::Carryover,
                    $carryover,
                    $isFirstYear ? $hireDate->toDateString() : "$year-01-01",
                );
            }

            $vacationDates = $this->scheduleVacationsForYear($user, $year, $hireDate, $allowance + $carryover);
            $vacationUsage = 0.0;
            foreach ($vacationDates as $period) {
                $req = LeaveRequest::create([
                    'user_id'       => $user->id,
                    'leave_type_id' => $this->vacationTypeId,
                    'start_date'    => $period['start'],
                    'end_date'      => $period['end'],
                    'days_count'    => $period['days'],
                    'status'        => LeaveRequestStatus::Approved,
                    'approved_by'   => $user->id,
                ]);

                $this->postLeaveTransaction(
                    $user->id,
                    $this->vacationTypeId,
                    LeaveTransactionType::Usage,
                    -$period['days'],
                    $period['start'],
                    requestId: $req->id,
                );
                $vacationUsage += $period['days'];
            }

            $sickDates = $this->scheduleSickDaysForYear($user, $year, $hireDate);
            foreach ($sickDates as $date) {
                LeaveTransaction::create([
                    'user_id'       => $user->id,
                    'leave_type_id' => $this->sickTypeId,
                    'type'          => LeaveTransactionType::Usage,
                    'amount'        => -1,
                    'date'          => $date,
                    'note'          => 'Bolovanje',
                ]);
            }

            $remainingCarryover = max(0.0, $carryover - $vacationUsage);
            if ($remainingCarryover > 0 && $allocation->carryover_expires_on !== null) {
                $this->postLeaveTransaction(
                    $user->id,
                    $this->vacationTypeId,
                    LeaveTransactionType::Expiry,
                    -$remainingCarryover,
                    $allocation->carryover_expires_on->toDateString(),
                );
            }

            $consumedFromCarryover = min($vacationUsage, (float) $carryover);
            $consumedFromAllowance = $vacationUsage - $consumedFromCarryover;

            $previousYearBalance = max(0.0, (float) $allowance - $consumedFromAllowance);

            $this->maybeGrantPaidLeave($user, $year);
            $this->seedTimeEntriesForYear($user, $role, $year, $hireDate, $vacationDates, $sickDates);
        }

        $currentYear = (int) now()->format('Y');
        if ($currentYear > self::END_YEAR) {
            $carryover = min((int) round($previousYearBalance), self::CARRYOVER_MAX_DAYS);
            $this->createAllocation($user, $currentYear, self::VACATION_DAYS_PER_YEAR, $carryover, $hireDate, false);

            if ($carryover > 0) {
                $this->postLeaveTransaction(
                    $user->id,
                    $this->vacationTypeId,
                    LeaveTransactionType::Carryover,
                    $carryover,
                    "$currentYear-01-01",
                );

                $expiryDate = Carbon::create($currentYear, 1, 1)
                    ->addMonths(self::CARRYOVER_EXPIRY_MONTHS)
                    ->subDay();

                if (now()->gt($expiryDate)) {
                    $this->postLeaveTransaction(
                        $user->id,
                        $this->vacationTypeId,
                        LeaveTransactionType::Expiry,
                        -$carryover,
                        $expiryDate->toDateString(),
                    );
                }
            }
        }
    }

    private function createAllocation(User $user, int $year, int $allowance, int $carryover, Carbon $hireDate, bool $isFirstYear): LeaveAllocation
    {
        $expiresOn = $carryover > 0
            ? Carbon::create($year, 1, 1)->addMonths(self::CARRYOVER_EXPIRY_MONTHS)->subDay()
            : null;

        return LeaveAllocation::create([
            'user_id'              => $user->id,
            'leave_type_id'        => $this->vacationTypeId,
            'year'                 => $year,
            'allowance'            => $allowance,
            'carryover_amount'     => $carryover,
            'carryover_expires_on' => $expiresOn,
        ]);
    }

    /**
     * @return array<int, array{start: string, end: string, days: float}>
     */
    private function scheduleVacationsForYear(User $user, int $year, Carbon $hireDate, int $totalAvailable): array
    {
        $seed = abs(crc32($user->email . $year));
        $earliestStart = $year === $hireDate->year ? $hireDate->copy() : Carbon::create($year, 1, 1);
        $periods = [];

        $summerLength = 5 + ($seed % 11);
        $summerStartMonth = ($seed % 2 === 0) ? 7 : 8;
        $summerStartDay = 1 + (($seed >> 1) % 20);
        $summerStart = Carbon::create($year, $summerStartMonth, $summerStartDay);

        if ($summerStart->gte($earliestStart)) {
            $period = $this->buildPeriod($summerStart, $summerLength);
            if ($period !== null) {
                $periods[] = $period;
            }
        }

        if ($seed % 3 !== 0) {
            $winterStart = Carbon::create($year, 12, 22 + (($seed >> 2) % 5));
            if ($winterStart->gte($earliestStart)) {
                $winterLength = 3 + ($seed % 4);
                $period = $this->buildPeriod($winterStart, $winterLength);
                if ($period !== null) {
                    $periods[] = $period;
                }
            }
        }

        $singleCount = $seed % 4;
        for ($i = 0; $i < $singleCount; $i++) {
            $month = 1 + (($seed >> (3 + $i)) % 11);
            if ($month >= 7 && $month <= 8) {
                $month = ($month % 6) + 2;
            }
            $day = 1 + (($seed >> (5 + $i)) % 27);
            $singleDate = Carbon::create($year, $month, $day);
            if ($singleDate->lt($earliestStart) || $singleDate->isWeekend()) {
                continue;
            }
            if ($this->isHoliday($singleDate)) {
                continue;
            }
            $periods[] = [
                'start' => $singleDate->toDateString(),
                'end'   => $singleDate->toDateString(),
                'days'  => 1.0,
            ];
        }

        $totalDays = array_sum(array_column($periods, 'days'));
        if ($totalDays > $totalAvailable) {
            usort($periods, fn ($a, $b) => $b['days'] <=> $a['days']);
            $accumulated = 0.0;
            $kept = [];
            foreach ($periods as $p) {
                if ($accumulated + $p['days'] > $totalAvailable) {
                    continue;
                }
                $accumulated += $p['days'];
                $kept[] = $p;
            }
            $periods = $kept;
        }

        return $periods;
    }

    /**
     * @return array{start: string, end: string, days: float}|null
     */
    private function buildPeriod(Carbon $start, int $calendarLength): ?array
    {
        $end = $start->copy()->addDays($calendarLength - 1);

        $days = 0.0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend() && ! $this->isHoliday($cursor)) {
                $days += 1.0;
            }
            $cursor->addDay();
        }

        if ($days <= 0) {
            return null;
        }

        return [
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),
            'days'  => $days,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function scheduleSickDaysForYear(User $user, int $year, Carbon $hireDate): array
    {
        $seed = abs(crc32($user->email . $year . 'sick'));
        $count = $seed % 6;
        if ($count === 0) {
            return [];
        }

        $earliestStart = $year === $hireDate->year ? $hireDate->copy() : Carbon::create($year, 1, 1);
        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $month = 1 + (($seed >> $i) % 12);
            $day = 1 + (($seed >> (3 + $i)) % 27);
            $candidate = Carbon::create($year, $month, $day);
            if ($candidate->lt($earliestStart) || $candidate->isWeekend() || $this->isHoliday($candidate)) {
                continue;
            }
            $dates[] = $candidate->toDateString();
        }

        return array_values(array_unique($dates));
    }

    private function maybeGrantPaidLeave(User $user, int $year): void
    {
        $seed = abs(crc32($user->email . $year . 'paid'));
        if ($seed % 25 !== 0) {
            return;
        }

        $days = 5 + ($seed % 6);
        $grantDate = Carbon::create($year, 1 + ($seed % 10), 15);

        LeaveAllocation::create([
            'user_id'              => $user->id,
            'leave_type_id'        => $this->paidLeaveTypeId,
            'year'                 => $year,
            'allowance'            => $days,
            'carryover_amount'     => 0,
            'carryover_expires_on' => null,
        ]);

        LeaveTransaction::create([
            'user_id'       => $user->id,
            'leave_type_id' => $this->paidLeaveTypeId,
            'type'          => LeaveTransactionType::Adjustment,
            'amount'        => $days,
            'date'          => $grantDate->toDateString(),
            'note'          => 'Plaćeni dopust — bonus',
        ]);
    }

    private function postLeaveTransaction(int $userId, int $typeId, LeaveTransactionType $type, float $amount, string $date, ?int $requestId = null): void
    {
        LeaveTransaction::create([
            'user_id'          => $userId,
            'leave_type_id'    => $typeId,
            'leave_request_id' => $requestId,
            'type'             => $type,
            'amount'           => $amount,
            'date'             => $date,
        ]);
    }

    /**
     * @param  array<int, array{start: string, end: string, days: float}>  $vacations
     * @param  array<int, string>  $sickDays
     */
    private function seedTimeEntriesForYear(User $user, string $role, int $year, Carbon $hireDate, array $vacations, array $sickDays): void
    {
        $vacationDates = $this->expandVacationDates($vacations);
        $sickSet = array_flip($sickDays);

        $start = $year === $hireDate->year ? $hireDate->copy() : Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $batch = [];
        $now = now();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            if ($cursor->isWeekend() || $this->isHoliday($cursor) || isset($vacationDates[$date]) || isset($sickSet[$date])) {
                $cursor->addDay();
                continue;
            }

            $schedule = $this->pickSchedule($role, $user->email, $date);

            foreach ($schedule as [$activityName, $startT, $endT]) {
                $activityId = $this->activityIdsByName[$activityName] ?? null;
                if ($activityId === null) {
                    continue;
                }

                $batch[] = [
                    'user_id'          => $user->id,
                    'activity_id'      => $activityId,
                    'date'             => $date,
                    'started_at'       => $startT,
                    'ended_at'         => $endT,
                    'duration_minutes' => $this->minutesBetween($startT, $endT),
                    'notes'            => null,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            if (count($batch) >= 1000) {
                TimeEntry::insert($batch);
                $batch = [];
            }

            $cursor->addDay();
        }

        if (! empty($batch)) {
            TimeEntry::insert($batch);
        }
    }

    /**
     * @param  array<int, array{start: string, end: string, days: float}>  $vacations
     * @return array<string, true>
     */
    private function expandVacationDates(array $vacations): array
    {
        $result = [];
        foreach ($vacations as $period) {
            $cursor = Carbon::parse($period['start']);
            $end = Carbon::parse($period['end']);
            while ($cursor->lte($end)) {
                $result[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }
        return $result;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function pickSchedule(string $role, string $userEmail, string $date): array
    {
        $patterns = match ($role) {
            'developer' => $this->developerPatterns(),
            'designer'  => $this->designerPatterns(),
            'manager'   => $this->managerPatterns(),
            default     => $this->developerPatterns(),
        };

        $idx = abs(crc32($userEmail . $date . 'pattern')) % count($patterns);
        return $patterns[$idx];
    }

    /**
     * @return array<int, array<int, array{0: string, 1: string, 2: string}>>
     */
    private function developerPatterns(): array
    {
        return [
            [['Meetings','08:00','08:30'],['Backend Development','08:30','11:30'],['Code Review','11:30','12:30'],['Backend Development','13:00','16:00'],['Code Review','16:00','17:00']],
            [['Meetings','08:00','09:00'],['Frontend Development','09:00','12:00'],['Frontend Development','13:00','16:30'],['Code Review','16:30','17:00']],
            [['DevOps / Deployment','08:00','10:00'],['Backend Development','10:00','12:00'],['Meetings','13:00','14:00'],['DevOps / Deployment','14:00','16:00'],['Code Review','16:00','17:00']],
            [['Meetings','08:30','09:00'],['Backend Development','09:00','12:00'],['Code Review','13:00','14:30'],['Backend Development','14:30','16:30']],
            [['Backend Development','08:00','12:00'],['Backend Development','13:00','17:00']],
            [['Meetings','09:00','09:30'],['Code Review','09:30','12:00'],['Backend Development','13:00','15:00'],['Code Review','15:00','16:30'],['Backend Development','16:30','17:00']],
            [['Backend Development','08:00','10:00'],['Frontend Development','10:00','12:30'],['Backend Development','13:30','16:00'],['Code Review','16:00','17:00']],
        ];
    }

    /**
     * @return array<int, array<int, array{0: string, 1: string, 2: string}>>
     */
    private function designerPatterns(): array
    {
        return [
            [['Meetings','09:00','09:30'],['UI Design','09:30','12:30'],['UI Design','13:30','16:30'],['Meetings','16:30','17:00']],
            [['Prototyping','08:30','12:00'],['Meetings','13:00','14:00'],['Prototyping','14:00','17:00']],
            [['Meetings','09:00','09:30'],['Frontend Development','09:30','12:30'],['Frontend Development','13:30','16:30'],['Client Communication','16:30','17:00']],
            [['Client Communication','09:00','10:30'],['UI Design','10:30','12:30'],['Client Communication','13:30','15:00'],['UI Design','15:00','17:00']],
            [['UI Design','08:00','10:00'],['Prototyping','10:00','12:00'],['Meetings','13:00','14:00'],['Frontend Development','14:00','16:30'],['Client Communication','16:30','17:00']],
            [['UI Design','08:00','12:30'],['UI Design','13:30','17:00']],
            [['Meetings','09:00','09:30'],['UI Design','09:30','12:00'],['Client Communication','13:30','15:00'],['Prototyping','15:00','16:30']],
        ];
    }

    /**
     * @return array<int, array<int, array{0: string, 1: string, 2: string}>>
     */
    private function managerPatterns(): array
    {
        return [
            [['Meetings','09:00','10:30'],['Project Planning','10:30','12:30'],['Client Communication','13:30','15:00'],['Reporting','15:00','17:00']],
            [['Project Planning','08:30','11:00'],['Meetings','11:00','12:30'],['Client Communication','13:30','15:30'],['Reporting','15:30','17:00']],
            [['Client Communication','09:00','11:00'],['Meetings','11:00','12:30'],['Project Planning','13:30','16:00'],['Meetings','16:00','17:00']],
            [['Meetings','08:30','10:00'],['Reporting','10:00','12:00'],['Client Communication','13:00','14:30'],['Project Planning','14:30','17:00']],
            [['Project Planning','09:00','12:30'],['Meetings','13:30','15:00'],['Client Communication','15:00','17:00']],
            [['Meetings','08:30','12:00'],['Meetings','13:00','15:00'],['Reporting','15:00','17:00']],
            [['Reporting','09:00','11:30'],['Project Planning','11:30','12:30'],['Meetings','13:30','15:30'],['Client Communication','15:30','17:00']],
        ];
    }

    private function isHoliday(Carbon $date): bool
    {
        $md = $date->format('m-d');
        if (in_array($md, self::RECURRING_HOLIDAYS, true)) {
            return true;
        }

        $easter = $this->easterDate($date->year);
        foreach (self::EASTER_RELATIVE as $offset) {
            if ($date->equalTo($easter->copy()->addDays($offset))) {
                return true;
            }
        }

        return false;
    }

    private function easterDate(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;
        return Carbon::create($year, $month, $day);
    }

    private function lockPeriods(int $orgId): void
    {
        $current = Carbon::create(self::START_YEAR, 1, 1);
        $end = Carbon::create(self::END_YEAR, 12, 1);

        while ($current->lte($end)) {
            PeriodLock::firstOrCreate([
                'organisation_id' => $orgId,
                'year'            => $current->year,
                'month'           => $current->month,
            ]);
            $current->addMonth();
        }
    }

    private function minutesBetween(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        return ($eh * 60 + $em) - ($sh * 60 + $sm);
    }
}
