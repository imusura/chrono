<?php

namespace Database\Seeders;

use App\Models\TimeEntry;
use Illuminate\Database\Seeder;

class AliceMaySeeder extends Seeder
{
    public function run(): void
    {
        $userId = 3; // alice@chrono.test

        // Activity IDs available to Alice (Developer role)
        $backend  = 1; // Backend Development
        $review   = 2; // Code Review
        $devops   = 3; // DevOps / Deployment
        $frontend = 6; // Frontend Development
        $meetings = 7; // Meetings

        // Non-working days in May 2026 for org 2
        $nonWorking = ['2026-05-01', '2026-05-05', '2026-05-30'];

        // Typical day schedules: [activity_id, started_at, ended_at]
        $schedules = [
            // Sprint week pattern — heavy backend, some review
            'A' => [
                [$meetings,  '08:00', '08:30'],
                [$backend,   '08:30', '11:30'],
                [$review,    '11:30', '12:30'],
                [$backend,   '13:00', '16:00'],
                [$review,    '16:00', '17:00'],
            ],
            // Frontend focus day
            'B' => [
                [$meetings,  '08:00', '09:00'],
                [$frontend,  '09:00', '12:00'],
                [$frontend,  '13:00', '16:30'],
                [$review,    '16:30', '17:00'],
            ],
            // DevOps / deployment day
            'C' => [
                [$devops,    '08:00', '10:00'],
                [$backend,   '10:00', '12:00'],
                [$meetings,  '13:00', '14:00'],
                [$devops,    '14:00', '16:00'],
                [$review,    '16:00', '17:00'],
            ],
            // Light day (slightly under 8h)
            'D' => [
                [$meetings,  '08:30', '09:00'],
                [$backend,   '09:00', '12:00'],
                [$review,    '13:00', '14:30'],
                [$backend,   '14:30', '16:30'],
            ],
            // Full backend sprint
            'E' => [
                [$backend,   '08:00', '12:00'],
                [$backend,   '13:00', '17:00'],
            ],
        ];

        // Assign a schedule pattern to each working weekday in May
        $pattern = ['A', 'B', 'A', 'C', 'E', 'A', 'D', 'B', 'A', 'C', 'E', 'A', 'B', 'D', 'A', 'C', 'A', 'E', 'B', 'A', 'D'];
        $patternIdx = 0;

        for ($day = 1; $day <= 31; $day++) {
            $date = sprintf('2026-05-%02d', $day);
            $dow = date('N', strtotime($date)); // 1=Mon … 7=Sun

            // Skip weekends and non-working days
            if ($dow >= 6 || in_array($date, $nonWorking)) {
                continue;
            }

            $schedule = $schedules[$pattern[$patternIdx % count($pattern)]];
            $patternIdx++;

            foreach ($schedule as [$activityId, $start, $end]) {
                TimeEntry::create([
                    'user_id'     => $userId,
                    'activity_id' => $activityId,
                    'date'        => $date,
                    'started_at'  => $start,
                    'ended_at'    => $end,
                    'notes'       => null,
                ]);
            }
        }
    }
}
