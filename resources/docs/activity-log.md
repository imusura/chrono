# Activity log

Every ticket carries a chronological record of changes and discussion. It lives at the bottom of the [ticket detail sheet](/docs/working-with-tickets) under the heading **Activity**, and it interleaves two kinds of entries:

- **Activities** — automatic records of events and field changes
- **Comments** — anything anyone has written in the comment box

Both appear in time order, oldest at the top, so reading top-to-bottom gives you the ticket's full history.

## What gets logged

| Change | What you see |
|---|---|
| **Ticket created** | "[User] created this ticket" — or "Created via [API client name]" for tickets submitted through the [Integrations API](/docs/integrations-api) |
| **Ticket closed** | "[User] closed this ticket" |
| **Ticket reopened** | "[User] reopened this ticket" |
| **Title edited** | Old title struck through, new title in bold |
| **Description edited** | "edited the description" — old/new text not shown |
| **Status changed** | Old status badge → new status badge (with colors) |
| **Type changed** | Old type badge → new type badge (with colors) |
| **Priority changed** | Old priority → new priority |
| **Assignee changed** | "assigned to X", "unassigned X", or "reassigned from X to Y" |
| **Custom field changed** | "changed [field name] from [old] to [new]" |
| **Attachment added** | "attached **filename.pdf**" |
| **Attachment removed** | "removed attachment **filename.pdf**" |
| **Comment added** | Full comment shown inline with author and timestamp |

## Who's attributed

Each activity records the user who triggered it. You'll see their name and avatar next to the entry. Comments work the same way — every comment carries its author.

Tickets created by [API clients](/docs/api-clients) attribute the creation entry to the integration name rather than a user.

## Who can see the log

Anyone who can see the ticket can see its full activity log. There's no per-entry visibility — if you can read the ticket, you can read every activity and comment on it.

In practice that means:

- **Admins and agents** see the activity log on every ticket in the project.
- **Clients** see the activity log on the tickets they themselves created.

## Immutability

Activities can't be edited or deleted. Once a change is recorded it's permanent — including comments. If a comment was made in error, the convention is to add a new comment correcting it rather than removing the original.

The only way an activity disappears is if the underlying ticket is deleted (in which case the entire history goes with it).
