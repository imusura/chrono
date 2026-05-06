<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class TicketTypeStatusSeeder extends Seeder
{
    public function run(): void
    {
        Project::all()->each(function (Project $project) {
            $types = $project->ticketTypes;
            $statuses = $project->ticketStatuses()->orderBy('id')->get();
            $closedStatus = $statuses->firstWhere('slug', 'closed');

            foreach ($types as $type) {
                foreach ($statuses as $index => $status) {
                    $type->statuses()->attach($status->id, [
                        'sort_order' => $index,
                        'is_final' => $closedStatus && $status->id === $closedStatus->id,
                    ]);
                }
            }
        });
    }
}
