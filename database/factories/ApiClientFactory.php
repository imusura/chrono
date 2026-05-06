<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiClient;
use App\Models\Project;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    public function definition(): array
    {
        $token = ApiClient::generateToken();

        return [
            'project_id' => Project::factory(),
            'name' => fake()->company().' Integration',
            'token_hash' => $token['hash'],
            'default_ticket_type_id' => TicketType::factory(),
            'is_active' => true,
            'last_used_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forProject(Project $project, ?TicketType $type = null): static
    {
        return $this->state([
            'project_id' => $project->id,
            'default_ticket_type_id' => $type?->id ?? $project->ticketTypes()->first()?->id ?? TicketType::factory(),
        ]);
    }
}
