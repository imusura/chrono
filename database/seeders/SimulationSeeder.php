<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Organisation;
use App\Models\PeriodLock;
use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds 2 users with 3 years of realistic time entries (2023-01-01 to 2025-12-31).
 *
 * Run after DatabaseSeeder (requires org + activities to exist).
 * Usage: php artisan db:seed --class=SimulationSeeder
 */
class SimulationSeeder extends Seeder
{
    // Activity IDs as created by DatabaseSeeder (in insertion order)
    private const BACKEND   = 1;
    private const REVIEW    = 2;
    private const DEVOPS    = 3;
    private const UI_DESIGN = 4;
    private const PROTOTYPE = 5;
    private const FRONTEND  = 6;
    private const MEETINGS  = 7;
    private const PLANNING  = 8;
    private const REPORTING = 9;
    private const CLIENT    = 10;

    // Croatian public holidays that recur every year (MM-DD)
    private const RECURRING_HOLIDAYS = [
        '01-01', // New Year's Day
        '01-06', // Epiphany
        '05-01', // Labour Day
        '06-22', // Anti-Fascist Struggle Day
        '08-05', // Victory and Homeland Thanksgiving Day
        '08-15', // Assumption of Mary
        '10-08', // Independence Day
        '11-01', // All Saints' Day
        '12-25', // Christmas
        '12-26', // St. Stephen's Day
    ];

    // Easter-relative holidays (offset from Easter Sunday in days)
    private const EASTER_RELATIVE = [
        0  => 'Easter Sunday',
        1  => 'Easter Monday',
        60 => 'Corpus Christi',
    ];

    public function run(): void
    {
        $org = Organisation::first();
        if (! $org) {
            $this->command->error('Run DatabaseSeeder first.');
            return;
        }

        // Resolve activity IDs dynamically in case the org ID differs
        $activityOffset = Activity::where('organisation_id', $org->id)->min('id') - 1;

        $devRole = Role::where('organisation_id', $org->id)->where('name', 'Developer')->first();
        $desRole = Role::where('organisation_id', $org->id)->where('name', 'Designer')->first();

        // Create or retrieve the two simulation users
        $alice = $this->makeUser('Alice Dev', 'alice.sim@chrono.test', $org->id, 8.00, $devRole);
        $bob   = $this->makeUser('Bob Designer', 'bob.sim@chrono.test', $org->id, 8.00, $desRole);

        $startDate = Carbon::create(2023, 1, 1);
        $endDate   = Carbon::create(2025, 12, 31);

        $holidays = $this->buildHolidaySet(2023, 2025);

        $this->command->info('Seeding Alice (Developer) — 3 years...');
        $this->seedUser($alice, $activityOffset, $startDate->copy(), $endDate->copy(), $holidays, 'developer');

        $this->command->info('Seeding Bob (Designer) — 3 years...');
        $this->seedUser($bob, $activityOffset, $startDate->copy(), $endDate->copy(), $holidays, 'designer');

        // Lock all months from 2023-01 through 2025-10 for the organisation
        $this->command->info('Locking historical periods...');
        $this->lockPeriods($org->id, 2023, 1, 2025, 10);

        $this->command->info('Done. alice.sim@chrono.test / bob.sim@chrono.test, password: password');
    }

    // -------------------------------------------------------------------------
    // User creation
    // -------------------------------------------------------------------------

    private function makeUser(string $name, string $email, int $orgId, float $hours, Role $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name'             => $name,
            'password'         => Hash::make('password'),
            'organisation_id'  => $orgId,
            'contracted_hours' => $hours,
        ]);

        if (! $user->roles()->where('role_id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }

        // Clear any existing simulation entries to allow re-seeding
        $user->timeEntries()->delete();

        return $user;
    }

    // -------------------------------------------------------------------------
    // Per-user seeding
    // -------------------------------------------------------------------------

    private function seedUser(
        User $user,
        int $activityOffset,
        Carbon $start,
        Carbon $end,
        array $holidays,
        string $role
    ): void {
        $batch = [];
        $current = $start->copy();
        $seed = crc32($user->email); // deterministic per user

        while ($current->lte($end)) {
            $date = $current->toDateString();
            $dow  = $current->dayOfWeek; // 0=Sun … 6=Sat

            // Skip weekends and holidays
            if ($dow === 0 || $dow === 6 || isset($holidays[$date])) {
                $current->addDay();
                continue;
            }

            // Occasionally simulate a sick/leave day (~5% of working days)
            if ($this->pseudoRand($seed, $date, 'absent') < 5) {
                $current->addDay();
                continue;
            }

            $schedule = $this->pickSchedule($role, $seed, $date);

            foreach ($schedule as [$activityId, $start_t, $end_t]) {
                $realActivityId = $activityId + $activityOffset;
                $duration = $this->minutesBetween($start_t, $end_t);
                $batch[] = [
                    'user_id'          => $user->id,
                    'activity_id'      => $realActivityId,
                    'date'             => $date,
                    'started_at'       => $start_t,
                    'ended_at'         => $end_t,
                    'duration_minutes' => $duration,
                    'notes'            => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            // Flush in chunks to avoid memory issues
            if (count($batch) >= 500) {
                TimeEntry::insert($batch);
                $batch = [];
            }

            $current->addDay();
        }

        if (! empty($batch)) {
            TimeEntry::insert($batch);
        }
    }

    // -------------------------------------------------------------------------
    // Schedule patterns
    // -------------------------------------------------------------------------

    private function pickSchedule(string $role, int $seed, string $date): array
    {
        $patterns = $role === 'developer'
            ? $this->developerPatterns()
            : $this->designerPatterns();

        $idx = $this->pseudoRand($seed, $date, 'pattern') % count($patterns);
        return $patterns[$idx];
    }

    private function developerPatterns(): array
    {
        $B = self::BACKEND;
        $R = self::REVIEW;
        $D = self::DEVOPS;
        $F = self::FRONTEND;
        $M = self::MEETINGS;

        return [
            // Pattern 0: Sprint day — heavy backend + review
            [[$M,'08:00','08:30'],[$B,'08:30','11:30'],[$R,'11:30','12:30'],[$B,'13:00','16:00'],[$R,'16:00','17:00']],
            // Pattern 1: Frontend focus
            [[$M,'08:00','09:00'],[$F,'09:00','12:00'],[$F,'13:00','16:30'],[$R,'16:30','17:00']],
            // Pattern 2: DevOps day
            [[$D,'08:00','10:00'],[$B,'10:00','12:00'],[$M,'13:00','14:00'],[$D,'14:00','16:00'],[$R,'16:00','17:00']],
            // Pattern 3: Light day
            [[$M,'08:30','09:00'],[$B,'09:00','12:00'],[$R,'13:00','14:30'],[$B,'14:30','16:30']],
            // Pattern 4: Full backend sprint
            [[$B,'08:00','12:00'],[$B,'13:00','17:00']],
            // Pattern 5: Code review heavy
            [[$M,'09:00','09:30'],[$R,'09:30','12:00'],[$B,'13:00','15:00'],[$R,'15:00','16:30'],[$B,'16:30','17:00']],
            // Pattern 6: Mixed dev + frontend
            [[$B,'08:00','10:00'],[$F,'10:00','12:30'],[$B,'13:30','16:00'],[$R,'16:00','17:00']],
        ];
    }

    private function designerPatterns(): array
    {
        $U = self::UI_DESIGN;
        $P = self::PROTOTYPE;
        $F = self::FRONTEND;
        $M = self::MEETINGS;
        $C = self::CLIENT;

        return [
            // Pattern 0: UI design day
            [[$M,'09:00','09:30'],[$U,'09:30','12:30'],[$U,'13:30','16:30'],[$M,'16:30','17:00']],
            // Pattern 1: Prototyping day
            [[$P,'08:30','12:00'],[$M,'13:00','14:00'],[$P,'14:00','17:00']],
            // Pattern 2: Frontend implementation
            [[$M,'09:00','09:30'],[$F,'09:30','12:30'],[$F,'13:30','16:30'],[$C,'16:30','17:00']],
            // Pattern 3: Client communication heavy
            [[$C,'09:00','10:30'],[$U,'10:30','12:30'],[$C,'13:30','15:00'],[$U,'15:00','17:00']],
            // Pattern 4: Handoff day — mix of design + prototype
            [[$U,'08:00','10:00'],[$P,'10:00','12:00'],[$M,'13:00','14:00'],[$F,'14:00','16:30'],[$C,'16:30','17:00']],
            // Pattern 5: Deep design focus
            [[$U,'08:00','12:30'],[$U,'13:30','17:00']],
            // Pattern 6: Light day
            [[$M,'09:00','09:30'],[$U,'09:30','12:00'],[$C,'13:30','15:00'],[$P,'15:00','16:30']],
        ];
    }

    // -------------------------------------------------------------------------
    // Holidays
    // -------------------------------------------------------------------------

    private function buildHolidaySet(int $fromYear, int $toYear): array
    {
        $set = [];

        for ($year = $fromYear; $year <= $toYear; $year++) {
            // Fixed date holidays
            foreach (self::RECURRING_HOLIDAYS as $md) {
                $set["$year-$md"] = true;
            }

            // Easter-relative holidays
            $easter = $this->easterDate($year);
            foreach (self::EASTER_RELATIVE as $offset => $name) {
                $set[$easter->copy()->addDays($offset)->toDateString()] = true;
            }
        }

        return $set;
    }

    private function easterDate(int $year): Carbon
    {
        // Anonymous Gregorian algorithm
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
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    // -------------------------------------------------------------------------
    // Period locks
    // -------------------------------------------------------------------------

    private function lockPeriods(int $orgId, int $fromYear, int $fromMonth, int $toYear, int $toMonth): void
    {
        $current = Carbon::create($fromYear, $fromMonth, 1);
        $end     = Carbon::create($toYear, $toMonth, 1);

        while ($current->lte($end)) {
            PeriodLock::firstOrCreate([
                'organisation_id' => $orgId,
                'year'            => $current->year,
                'month'           => $current->month,
            ]);
            $current->addMonth();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function minutesBetween(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        return ($eh * 60 + $em) - ($sh * 60 + $sm);
    }

    /**
     * Deterministic pseudo-random value 0-99 based on seed + date + key.
     * Avoids using rand() so results are reproducible across re-runs.
     */
    private function pseudoRand(int $seed, string $date, string $key): int
    {
        return abs(crc32($seed . $date . $key)) % 100;
    }
}
