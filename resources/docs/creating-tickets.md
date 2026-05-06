# Creating tickets

All roles can create tickets. Click **New Ticket** at the top right of the ticket list (or the floating **+** button on mobile).

## Fields

| Field | Required | Notes |
|---|---|---|
| **Title** | Yes | A short summary, up to 255 characters. |
| **Type** | Yes | Defaults to whichever type the admin marked as default. Changing the type may show different custom fields. |
| **Priority** | Yes | Low, Medium (default), High, or Urgent. |
| **Description** | Yes | Rich text — bold, italic, headings, bullet and ordered lists. |
| **Custom fields** | Varies | Only shown if the selected type has custom fields. Required ones are marked with a red asterisk. |

The ticket's **status** is set automatically to the first status in the selected type's workflow — you don't pick it on creation.

## Who's the reporter?

Whoever creates the ticket is recorded as the reporter and shown in the **People** section of the detail sheet. Tickets created by [API clients](/docs/api-clients) show the integration name plus any submitter info the integration sent (name and email).

## Assignment

If the project has a [default assignee](/docs/managing-members#default-assignee) set, new tickets are auto-assigned to that person. Otherwise the ticket starts as **Unassigned**, and an agent or admin can pick it up later.

## Attachments

Attachments aren't part of the create form — open the ticket after creating it and use the attachments section. See [Working with tickets](/docs/working-with-tickets#attachments).

## What clients can do

Clients can create tickets like anyone else. After creation, they can edit the title, description, status, priority, and custom fields on their own tickets. They **cannot change the ticket type** or reassign the ticket — comment on it and an agent or admin will handle those.
