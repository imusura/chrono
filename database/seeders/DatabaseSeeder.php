<?php

namespace Database\Seeders;

use App\Enums\ProjectRole;
use App\Enums\TicketPriority;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@ticketing.test',
            'is_super_admin' => true,
            'can_create_projects' => true,
        ]);

        $agent1 = User::factory()->create([
            'name' => 'Sarah Agent',
            'email' => 'sarah@ticketing.test',
        ]);

        $agent2 = User::factory()->create([
            'name' => 'Mike Agent',
            'email' => 'mike@ticketing.test',
        ]);

        $clients = collect([
            User::factory()->create(['name' => 'Alice Client', 'email' => 'alice@example.com']),
            User::factory()->create(['name' => 'Bob Client', 'email' => 'bob@example.com']),
            User::factory()->create(['name' => 'Carol Client', 'email' => 'carol@example.com']),
            User::factory()->create(['name' => 'Dave Client', 'email' => 'dave@example.com']),
        ]);

        $project = Project::create([
            'name' => 'Acme Support',
            'slug' => 'acme-support',
            'prefix' => 'ACME',
            'default_assignee_id' => $agent1->id,
        ]);

        $project->members()->attach($admin->id, ['role' => ProjectRole::Admin->value]);
        $project->members()->attach($agent1->id, ['role' => ProjectRole::Agent->value]);
        $project->members()->attach($agent2->id, ['role' => ProjectRole::Agent->value]);
        $clients->each(fn (User $client) => $project->members()->attach($client->id, ['role' => ProjectRole::Client->value]));

        $this->call([
            TicketTypeSeeder::class,
            TicketStatusSeeder::class,
            TicketTypeStatusSeeder::class,
        ]);

        $project->refresh();
        $statuses = $project->ticketStatuses;
        $open = $statuses->firstWhere('slug', 'open');
        $inProgress = $statuses->firstWhere('slug', 'in-progress');
        $waitingOnClient = $statuses->firstWhere('slug', 'waiting-on-client');
        $resolved = $statuses->firstWhere('slug', 'resolved');
        $closed = $statuses->firstWhere('slug', 'closed');

        $agents = collect([$agent1, $agent2]);
        $allUsers = $clients->merge($agents)->push($admin);

        $tickets = collect();

        $tickets = $tickets->merge(
            Ticket::factory(4)
                ->forProject($project)
                ->sequence(fn () => ['created_by' => $clients->random()->id])
                ->withStatus($open)
                ->create()
        );

        $tickets = $tickets->merge(
            Ticket::factory(5)
                ->forProject($project)
                ->sequence(fn () => [
                    'created_by' => $clients->random()->id,
                    'assigned_to' => $agents->random()->id,
                ])
                ->withStatus($inProgress)
                ->create()
        );

        $tickets = $tickets->merge(
            Ticket::factory(3)
                ->forProject($project)
                ->sequence(fn () => [
                    'created_by' => $clients->random()->id,
                    'assigned_to' => $agents->random()->id,
                ])
                ->withStatus($waitingOnClient)
                ->create()
        );

        $tickets = $tickets->merge(
            Ticket::factory(6)
                ->forProject($project)
                ->sequence(fn () => [
                    'created_by' => $clients->random()->id,
                    'assigned_to' => $agents->random()->id,
                ])
                ->withStatus($resolved)
                ->create()
        );

        $tickets = $tickets->merge(
            Ticket::factory(4)
                ->forProject($project)
                ->sequence(fn () => [
                    'created_by' => $clients->random()->id,
                    'assigned_to' => $agents->random()->id,
                ])
                ->withStatus($closed)
                ->create()
        );

        $tickets = $tickets->merge(
            Ticket::factory(3)
                ->forProject($project)
                ->withPriority(TicketPriority::Urgent)
                ->sequence(fn () => [
                    'created_by' => $clients->random()->id,
                    'assigned_to' => fake()->boolean(70) ? $agents->random()->id : null,
                    'status_id' => fake()->randomElement([$open->id, $inProgress->id]),
                ])
                ->create()
        );

        $tickets->each(function (Ticket $ticket) use ($allUsers) {
            $commentCount = fake()->numberBetween(1, 5);

            Comment::factory($commentCount)
                ->forTicket($ticket)
                ->sequence(fn () => [
                    'user_id' => $allUsers->random()->id,
                    'created_at' => fake()->dateTimeBetween($ticket->created_at, 'now'),
                ])
                ->create();
        });

        $project->update(['next_ticket_number' => $tickets->count() + 1]);
    }
}
