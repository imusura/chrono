# List view

The default view when you open a project. A table of tickets you can sort, filter, and page through.

Clients see only the tickets they themselves created here. Agents and admins see everything in the project.

## Columns

| Column | Sortable |
|---|---|
| **Type** | No (icon only) |
| **ID** | No (e.g. `PROJ-123`) |
| **Title** | Yes |
| **Status** | Yes |
| **Priority** | Yes |
| **Client** | No (the ticket's reporter) |
| **Assignee** | No (shows "Unassigned" if nobody's picked it up) |
| **Created** | Yes |

Click a sortable column header to sort by it; click again to flip direction.

Closed tickets are shown at reduced opacity so they fade into the background without disappearing.

## Filters

The toolbar above the table:

- **Search** — full-text across ticket fields. Debounced, so it waits for you to stop typing before searching.
- **Type** — filter to a single type (or "All Types").
- **Status** — filter to a single status (or "All Statuses").
- **Priority** — Low, Medium, High, Urgent, or all.
- **Created date** — a date range (From/To) for when the ticket was created.

Filters combine with AND. Changing a filter resets you to page 1.

## Quick status edits

Anyone with edit permission can change a ticket's status directly from the table — click the status badge in a row and pick the new status from the popover. All statuses in the type's workflow are available.

## Opening a ticket

Click any row to open the **detail sheet** — a panel that slides in from the right with the full ticket: description, comments, activity, attachments, and the People/Dates sidebar. Everything you can do to a ticket lives in this sheet. See [Working with tickets](/docs/working-with-tickets).

## Mobile

The table collapses to a card layout on smaller screens. Each card shows the type icon, ID, priority, status, title, assignee, and created date. Filters and the create button are still available.
