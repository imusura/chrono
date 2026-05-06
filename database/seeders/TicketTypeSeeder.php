<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Bug', 'slug' => 'bug', 'color' => 'red', 'icon' => 'bug', 'is_default' => false],
            ['name' => 'Feature', 'slug' => 'feature', 'color' => 'purple', 'icon' => 'lightbulb', 'is_default' => false],
            ['name' => 'Task', 'slug' => 'task', 'color' => 'blue', 'icon' => 'list-checks', 'is_default' => true],
            ['name' => 'Question', 'slug' => 'question', 'color' => 'yellow', 'icon' => 'help-circle', 'is_default' => false],
        ];

        Project::all()->each(function (Project $project) use ($types) {
            foreach ($types as $type) {
                TicketType::create([...$type, 'project_id' => $project->id]);
            }
        });
    }
}
