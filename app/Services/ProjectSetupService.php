<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\TicketType;

class ProjectSetupService
{
    public const TEMPLATES = [
        'software' => [
            'label' => 'Software Development',
            'description' => 'Bug tracking, features, and dev tasks',
            'icon' => 'code',
            'types' => [
                ['name' => 'Bug', 'slug' => 'bug', 'color' => 'red', 'icon' => 'bug', 'is_default' => false],
                ['name' => 'Feature', 'slug' => 'feature', 'color' => 'purple', 'icon' => 'lightbulb', 'is_default' => false],
                ['name' => 'Task', 'slug' => 'task', 'color' => 'blue', 'icon' => 'list-checks', 'is_default' => true],
                ['name' => 'Improvement', 'slug' => 'improvement', 'color' => 'teal', 'icon' => 'trending-up', 'is_default' => false],
            ],
            'statuses' => [
                ['name' => 'Open', 'slug' => 'open', 'color' => 'blue'],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'yellow'],
                ['name' => 'In Review', 'slug' => 'in-review', 'color' => 'purple'],
                ['name' => 'Resolved', 'slug' => 'resolved', 'color' => 'green'],
                ['name' => 'Closed', 'slug' => 'closed', 'color' => 'gray'],
            ],
        ],
        'support' => [
            'label' => 'Customer Support',
            'description' => 'Help desk and customer requests',
            'icon' => 'headphones',
            'types' => [
                ['name' => 'Question', 'slug' => 'question', 'color' => 'yellow', 'icon' => 'help-circle', 'is_default' => true],
                ['name' => 'Problem', 'slug' => 'problem', 'color' => 'red', 'icon' => 'alert-triangle', 'is_default' => false],
                ['name' => 'Request', 'slug' => 'request', 'color' => 'blue', 'icon' => 'message-square', 'is_default' => false],
            ],
            'statuses' => [
                ['name' => 'Open', 'slug' => 'open', 'color' => 'blue'],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'yellow'],
                ['name' => 'Waiting on Client', 'slug' => 'waiting-on-client', 'color' => 'orange'],
                ['name' => 'Resolved', 'slug' => 'resolved', 'color' => 'green'],
                ['name' => 'Closed', 'slug' => 'closed', 'color' => 'gray'],
            ],
        ],
        'general' => [
            'label' => 'General',
            'description' => 'Simple task tracking',
            'icon' => 'clipboard-list',
            'types' => [
                ['name' => 'Task', 'slug' => 'task', 'color' => 'blue', 'icon' => 'list-checks', 'is_default' => true],
                ['name' => 'Issue', 'slug' => 'issue', 'color' => 'red', 'icon' => 'alert-circle', 'is_default' => false],
            ],
            'statuses' => [
                ['name' => 'To Do', 'slug' => 'to-do', 'color' => 'blue'],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => 'yellow'],
                ['name' => 'Done', 'slug' => 'done', 'color' => 'green'],
            ],
        ],
        'blank' => [
            'label' => 'Blank',
            'description' => "I'll configure types and statuses later",
            'icon' => 'settings',
            'types' => [],
            'statuses' => [],
        ],
    ];

    public function seedFromTemplate(Project $project, string $template = 'software'): void
    {
        $config = self::TEMPLATES[$template] ?? self::TEMPLATES['software'];

        $types = collect($config['types'])->map(
            fn (array $type) => TicketType::create([...$type, 'project_id' => $project->id])
        );

        $statuses = collect($config['statuses'])->map(
            fn (array $status) => TicketStatus::create([...$status, 'project_id' => $project->id])
        );

        $lastStatus = $statuses->last();

        foreach ($types as $type) {
            foreach ($statuses as $index => $status) {
                $type->statuses()->attach($status->id, [
                    'sort_order' => $index,
                    'is_final' => $status->id === $lastStatus->id,
                ]);
            }
        }
    }

    /** @return array<string, array{label: string, description: string, icon: string}> */
    public static function templateOptions(): array
    {
        return collect(self::TEMPLATES)->map(fn (array $t) => [
            'label' => $t['label'],
            'description' => $t['description'],
            'icon' => $t['icon'],
        ])->all();
    }
}
