<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        $richComments = [
            '<p>Thanks for reporting this. I\'m looking into it now and will update you shortly.</p>',
            '<p>I was able to reproduce the issue. It seems to be related to the <strong>recent deployment</strong>. Working on a fix.</p>',
            '<p>Could you provide more details?</p><ul><li>What browser are you using?</li><li>When did this start happening?</li><li>Any error messages you can share?</li></ul>',
            '<p>Fix has been deployed to staging. <strong>Please test and let me know if the issue persists.</strong></p>',
            '<p>I\'ve tested the fix and it\'s working now. Thank you for the quick turnaround!</p>',
            '<p>This is still happening for me. The error occurs specifically when:</p><ol><li>I navigate to the settings page</li><li>Change any value</li><li>Click save</li></ol><p>The page just refreshes without saving.</p>',
            '<p>We\'ve identified the root cause — it was a <strong>database connection timeout</strong> during peak hours. We\'re scaling up the connection pool.</p>',
            '<p>Closing this as resolved. The fix was deployed in <strong>v2.4.1</strong>. Please reopen if you experience this again.</p>',
            '<p>I\'m experiencing the same issue. Started about 2 hours ago. Seems intermittent — sometimes it works, sometimes it doesn\'t.</p>',
            '<p>Update: We\'ve rolled back the change that caused this. Everything should be working normally now. We\'ll push a proper fix in the next release.</p>',
        ];

        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'content' => fake()->randomElement($richComments),
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    public function byUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function forTicket(Ticket $ticket): static
    {
        return $this->state(['ticket_id' => $ticket->id]);
    }
}
