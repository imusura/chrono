# Working with tickets

Clicking any ticket — from the list, from the board, or from search — opens the **detail sheet**: a panel that slides in from the right and contains everything about the ticket.

## Layout

The sheet has two regions:

- **Main area** — title, description, custom fields, attachments, and the activity timeline (comments and changes).
- **Sidebar (right)** — People (assignee, reporter), Dates (created, updated, closed), submission context (for tickets created via [API clients](/docs/api-clients)), and admin/agent actions like **Close ticket** and **Reopen ticket**.

## Editing fields

Hover over the title or description and a pencil icon appears — click to edit inline. Title saves on Enter or blur; Escape cancels. Description uses the same rich text editor as the create form.

Type, status, priority, assignee, and custom fields are all dropdowns or selects in the sidebar — change them and they save immediately.

Clients can edit title, description, status, priority, and custom fields on their own tickets. They **cannot** change the ticket type or reassign. See [Roles](/docs/roles#client) for the full breakdown.

## Status

The status selector shows every status in the ticket type's workflow. Any user with edit permission can move a ticket to any status — there are no forced transition rules. If a status move was a mistake, just move it back.

## Activity and comments

Below the main fields, the **Activity** section interleaves comments with automatic records of field changes (status, priority, assignee, title, description, attachments) in chronological order. It's how you read the ticket's full history end to end.

Type into the comment editor at the bottom of the section and click **Add Comment** to add to the conversation. All roles can comment, including clients on their own tickets — comments are visible to everyone who can see the ticket.

For the full breakdown of what is and isn't tracked, attribution rules, and visibility, see [Activity log](/docs/activity-log).

## Attachments

Drag files into the attachments area, or click to pick.

- **Max 10 MB per file**
- **Allowed types:** images (JPG, PNG, GIF, WebP), PDF, Office documents (DOC/DOCX/XLS/XLSX), CSV, TXT, LOG, ZIP, GZ
- Multiple files can be uploaded at once

Image attachments open in a lightbox when clicked, with arrow-key navigation between images. Other file types download.

Agents and admins can delete any attachment. Clients can manage attachments only on their own tickets.

## Closing and reopening

Once a ticket is in a final status (typically **Closed** — defined per type in [Configure → Types & Statuses](/docs/configuring-a-project#workflow-per-type)), the sidebar shows a **Close ticket** action. Closed tickets show a "Closed" badge on their title and a closed-at timestamp in the sidebar.

Reopen a closed ticket from the same place — the sidebar swaps the button to **Reopen ticket**, which moves it back to the previous status.
