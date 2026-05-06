# Kanban board

Click **Board** in a project's tab bar. The board shows one column per active status, with tickets as draggable cards.

Clients see only their own tickets here, in a read-only view. Agents and admins see all project tickets and can drag cards.

## Columns

One column per active status, in workflow order. Each column header shows:

- The status name and its color/icon
- A count of tickets currently in that column
- A small "final" label if the status is marked as final (e.g. Closed)

## Cards

Each card shows:

- The ticket title (truncated to two lines)
- The reference ID (e.g. `PROJ-42`)
- The type icon
- The priority icon
- The assignee's avatar (if assigned)

Click a card to open the [detail sheet](/docs/working-with-tickets).

## Drag and drop

Agents and admins can drag cards between columns to change a ticket's status, or reorder them within a column. Any status is a valid drop target — there are no forced transition rules.

## Filters

Above the board:

- **Client** — show only tickets reported by a specific client
- **Type**
- **Priority**
- **Search**

Filters apply across all columns simultaneously.

## When to use the board vs the list

The board is best for **flow** — seeing where work is stuck, picking up new tickets, moving things along. The [list view](/docs/list-view) is better for **triage and bulk scanning** — sorting, date ranges, and seeing many tickets at once with full columns visible.
