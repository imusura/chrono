<?php

declare(strict_types=1);

namespace Tests\Feature\Integrations\V1;

use App\Models\ApiClient;
use App\Models\Project;
use App\Models\Ticket;
use App\Services\ProjectSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CreateTicketTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/integrations/v1/tickets';

    private Project $project;

    private ApiClient $client;

    private string $plainToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'prefix' => 'TST',
            'next_ticket_number' => 1,
        ]);

        app(ProjectSetupService::class)->seedFromTemplate($this->project, 'support');

        $type = $this->project->ticketTypes()->where('is_default', true)->firstOrFail();

        $token = ApiClient::generateToken();
        $this->plainToken = $token['plain'];

        $this->client = $this->project->apiClients()->create([
            'name' => 'Test Integration',
            'token_hash' => $token['hash'],
            'default_ticket_type_id' => $type->id,
            'is_active' => true,
        ]);

        RateLimiter::clear('api-client:'.$this->client->id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Hello from Household',
            'description' => '<p>Something is broken.</p>',
            'submitter_email' => 'user@example.com',
            'submitter_name' => 'Jane Doe',
            'metadata' => ['page' => '/feedback', 'app_version' => '1.0.0'],
        ], $overrides);
    }

    private function authHeaders(?string $token = null, ?string $idempotencyKey = null): array
    {
        $headers = ['Authorization' => 'Bearer '.($token ?? $this->plainToken)];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $headers;
    }

    public function test_creates_a_ticket_with_201_and_empty_body(): void
    {
        $response = $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertSame('', $response->getContent());

        $ticket = Ticket::sole();
        $this->assertSame('TST-1', $ticket->reference_id);
        $this->assertSame('Hello from Household', $ticket->title);
        $this->assertNull($ticket->created_by);
        $this->assertSame($this->client->id, $ticket->created_via_api_client_id);
        $this->assertSame('user@example.com', $ticket->submitter_email);
        $this->assertSame(['page' => '/feedback', 'app_version' => '1.0.0'], $ticket->metadata);
        $this->assertSame(2, $this->project->fresh()->next_ticket_number);
        $this->assertNotNull($this->client->fresh()->last_used_at);
    }

    public function test_rejects_request_without_token(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload())->assertUnauthorized();
    }

    public function test_rejects_request_with_unknown_token(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(), ['Authorization' => 'Bearer tkt_nope'])
            ->assertUnauthorized();
    }

    public function test_rejects_inactive_client(): void
    {
        $this->client->update(['is_active' => false]);

        $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders())
            ->assertForbidden();
    }

    public function test_soft_deleted_client_returns_unauthorized(): void
    {
        $this->client->delete();

        $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders())
            ->assertUnauthorized();
    }

    public function test_validates_required_fields(): void
    {
        $this->postJson(self::ENDPOINT, ['subject' => ''], $this->authHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'description']);
    }

    public function test_idempotent_replay_does_not_create_a_duplicate(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders(idempotencyKey: 'key-12345678'))->assertCreated();
        $this->postJson(self::ENDPOINT, $this->payload(['subject' => 'different']), $this->authHeaders(idempotencyKey: 'key-12345678'))->assertCreated();

        $this->assertSame(1, Ticket::count());
    }

    public function test_invalid_idempotency_key_format_is_rejected(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders(idempotencyKey: 'short'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_metadata_too_large_is_rejected(): void
    {
        $big = str_repeat('a', 2000);

        $this->postJson(self::ENDPOINT, $this->payload(['metadata' => ['blob' => $big]]), $this->authHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['metadata']);
    }

    public function test_default_type_and_first_status_are_applied(): void
    {
        $this->postJson(self::ENDPOINT, $this->payload(), $this->authHeaders())->assertCreated();

        $ticket = Ticket::sole();
        $type = $this->project->ticketTypes()->where('is_default', true)->firstOrFail();
        $firstStatus = $type->statuses()->orderByPivot('sort_order')->first();

        $this->assertSame($type->id, $ticket->type_id);
        $this->assertSame($firstStatus->id, $ticket->status_id);
    }

    public function test_rate_limit_returns_429_after_60_requests(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson(self::ENDPOINT, $this->payload(['subject' => "S{$i}"]), $this->authHeaders())
                ->assertCreated();
        }

        $this->postJson(self::ENDPOINT, $this->payload(['subject' => 'overflow']), $this->authHeaders())
            ->assertStatus(429);
    }
}
