# Configuring a project

Open a project and click **Configure** in the tab bar. This tab is only visible to project admins.

Configure has four sub-tabs:

- **Types & Statuses** — what kinds of tickets you have and how they flow
- **Custom Fields** — extra data captured per ticket type
- **Members** — see [Managing members](/docs/managing-members)
- **Integrations** — see [API clients](/docs/api-clients)

## Types & Statuses

The most important tab. Tickets can't exist without at least one type and one status, and **each type defines its own workflow** — the ordered list of statuses tickets of that type can move through.

The panel has two columns:

- **Left:** all types and all statuses in the project. Click an item to select it. Click **+** next to either heading to create a new one.
- **Right:** the selected type's details and its workflow.

### Creating a type

Click **+** next to **Types**. You'll see:

| Field | Notes |
|---|---|
| **Name** | E.g. "Bug", "IT Request". |
| **Color** | One of 24 colors. Used in badges and on Kanban cards. |
| **Icon** | Optional. Pick from ~100 Lucide icons grouped by category (alerts, dev, communication, business, etc.). |
| **Default type** | If on, new tickets default to this type. Only one type can be the default. |

A type with no statuses in its workflow can't be used for new tickets — assign at least one status to its workflow before creating tickets of that type.

### Creating a status

Click **+** next to **Statuses**. Same shape as types: name, color, icon. Statuses are project-wide; the order they appear in a type's workflow is set per type.

### Workflow (per type)

Select a type on the left. The right panel shows its workflow — the ordered list of statuses tickets of this type can have.

- **Add statuses** by clicking them in the picker
- **Reorder** to define the forward flow
- **Mark a status as final** to indicate "done" (e.g. Closed)

Status transitions follow this order. Agents can move tickets forward one step at a time and backward freely; admins can jump to any status. See [Working with tickets](/docs/working-with-tickets#status).

### Activating and deactivating

Both types and statuses can be deactivated instead of deleted. Deactivated entries are hidden from the create-ticket form and from filters but remain on existing tickets, preserving history. Reactivate at any time.

## Custom Fields

Custom fields capture structured data beyond title and description. They're defined **per ticket type** — a Bug might have a "Steps to reproduce" field that doesn't apply to a Feature.

Pick a type at the top of the panel, then click **Add Field**.

| Field type | Use for |
|---|---|
| **Text** | Short single-line input |
| **Textarea** | Long free-form text |
| **Number** | Numeric value |
| **Date** | Calendar date |
| **Select** | One option from a list you define |
| **Checkbox** | Yes/no toggle |

For **Select** fields, add the available options inside the field dialog.

Mark a field as **Required** to enforce it on the create-ticket form. Required custom fields show a red asterisk and block submission if empty.

Custom fields appear on the create-ticket form when the matching type is chosen, and on the ticket detail sheet for editing later.
