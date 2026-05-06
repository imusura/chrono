<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    private static array $projectCounters = [];

    public function definition(): array
    {
        $titles = [
            'Cannot log in to my account',
            'Payment processing error on checkout',
            'Dashboard loading very slowly',
            'Email notifications not being received',
            'Unable to upload attachments',
            'Password reset link expired immediately',
            'Report export generates empty PDF',
            'Mobile app crashes on startup',
            'Search results showing incorrect data',
            'Two-factor authentication not working',
            'Invoice amounts are incorrect',
            'API rate limiting too aggressive',
            'User profile page returns 500 error',
            'Dark mode toggle does not persist',
            'CSV import fails with special characters',
            'Webhook delivery failing intermittently',
            'Calendar sync not updating events',
            'Permission denied when accessing admin panel',
            'Duplicate entries appearing in reports',
            'File download times out for large files',
            'Notification preferences not saving',
            'Auto-save feature losing draft content',
            'Pagination broken on search results',
            'SSO integration returning invalid token',
            'Bulk operations timing out',
        ];

        $richContents = [
            '<p>I\'ve been trying to log in for the past hour but keep getting an <strong>invalid credentials</strong> error. I\'ve already reset my password twice.</p><ul><li>Browser: Chrome 120</li><li>OS: Windows 11</li><li>Cleared cookies and cache</li></ul><p>This is blocking my work completely.</p>',
            '<p>When I try to complete a purchase, the payment form throws an error after submitting.</p><p>Steps to reproduce:</p><ol><li>Add any item to cart</li><li>Go to checkout</li><li>Fill in payment details</li><li>Click "Pay Now"</li></ol><p>Error message: <strong>"Transaction could not be processed"</strong></p>',
            '<p>The main dashboard takes over <strong>30 seconds</strong> to load. This started happening after the last update.</p><p>Other pages seem fine, but the dashboard with all the widgets is extremely slow. The network tab shows multiple API calls taking 5+ seconds each.</p>',
            '<p>I haven\'t received any email notifications for the past 3 days. I\'ve checked:</p><ul><li>Spam folder — nothing there</li><li>Email settings — all notifications enabled</li><li>Email address — confirmed correct</li></ul><p>Other team members seem to be receiving theirs fine.</p>',
            '<p>Whenever I try to upload a file larger than <strong>5MB</strong>, the upload fails silently. No error message appears, the progress bar just stops.</p><p>Smaller files work fine. I need to upload reports that are typically 10-20MB.</p>',
            '<p>The password reset link I received via email expires before I can use it. I click the link within <strong>seconds</strong> of receiving the email and it says the token is invalid.</p><p>I\'ve tried this 5 times now with the same result.</p>',
            '<p>When I export the monthly report as PDF, the generated file is completely empty — just blank pages.</p><p>The <strong>CSV export works fine</strong>, so the data is there. Only the PDF generation seems broken.</p>',
            '<p>Getting consistent crashes on the mobile app:</p><ol><li>Open the app</li><li>See the splash screen</li><li>App immediately closes</li></ol><p>Device: iPhone 15, iOS 17.2. Reinstalling didn\'t help. <strong>This is affecting our entire field team.</strong></p>',
        ];

        return [
            'title' => fake()->randomElement($titles),
            'content' => fake()->randomElement($richContents),
            'type_id' => TicketType::inRandomOrder()->first()?->id ?? TicketType::factory(),
            'status_id' => TicketStatus::inRandomOrder()->first()?->id ?? TicketStatus::factory(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Ticket $ticket) {
            if ($ticket->project_id) {
                $projectId = $ticket->project_id;
                if (! isset(static::$projectCounters[$projectId])) {
                    $max = Ticket::where('project_id', $projectId)->max('number') ?? 0;
                    static::$projectCounters[$projectId] = $max;
                }
                static::$projectCounters[$projectId]++;
                $number = static::$projectCounters[$projectId];

                $project = Project::find($projectId);
                $ticket->number = $number;
                $ticket->reference_id = "{$project->prefix}-{$number}";
            }
        });
    }

    public function forProject(Project $project): static
    {
        return $this->state(['project_id' => $project->id])
            ->state(fn () => [
                'type_id' => $project->ticketTypes()->inRandomOrder()->first()?->id,
                'status_id' => $project->ticketStatuses()->inRandomOrder()->first()?->id,
            ]);
    }

    public function withStatus(TicketStatus $status): static
    {
        return $this->state(['status_id' => $status->id]);
    }

    public function withType(TicketType $type): static
    {
        return $this->state(['type_id' => $type->id]);
    }

    public function withPriority(TicketPriority $priority): static
    {
        return $this->state(['priority' => $priority]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(['created_by' => $user->id]);
    }
}
