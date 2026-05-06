# Roles

Every project has three roles. A user gets exactly one role per project, assigned by a project admin.

## Admin

Project admins have full control over a single project.

- Read, edit, assign, and delete any ticket in the project
- Configure ticket types, statuses, custom fields, and the workflow between them
- Invite members and change their roles
- Set the default assignee for new tickets
- Issue, rotate, and revoke API tokens for external integrations
- Edit the project's name and settings

Admins do **not** automatically have access to other projects — each project's admin role is independent.

## Agent

Agents handle the day-to-day work of moving tickets through the workflow.

- Read, edit, and assign any ticket in the project (not just tickets assigned to them)
- Add comments, manage attachments, and change status, priority, type, and custom field values
- Cannot delete tickets
- Cannot access **Configure** (project settings, members, integrations)

Agents see the **Documentation** link in the sidebar; clients do not.

## Client

Clients are the people who submit tickets — typically end users requesting help.

- Create tickets in the project
- See **only the tickets they themselves created** — both in the list view and on the Kanban board
- Edit title, description, status, priority, and custom fields on their own tickets
- Add comments and attach files to their own tickets
- **Cannot change the ticket type** or reassign a ticket
- Cannot see other clients' tickets or access settings

## Who can create projects

Creating a new project is a separate permission from project roles. It's granted to specific users. If you don't see a "New Project" button on the projects page, you don't have it.
