<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Open', 'slug' => 'open', 'color' => 'blue'],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'yellow'],
            ['name' => 'Waiting on Client', 'slug' => 'waiting-on-client', 'color' => 'orange'],
            ['name' => 'Resolved', 'slug' => 'resolved', 'color' => 'green'],
            ['name' => 'Closed', 'slug' => 'closed', 'color' => 'gray'],
        ];

        Project::all()->each(function (Project $project) use ($statuses) {
            foreach ($statuses as $status) {
                TicketStatus::create([...$status, 'project_id' => $project->id]);
            }
        });
    }
}
