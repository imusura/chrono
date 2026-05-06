# Creating a project

Click **New Project** on the projects page. If the button isn't there, you don't have permission to create projects — ask an admin to grant it.

## Fields

| Field | Required | Notes |
|---|---|---|
| **Name** | Yes | Up to 255 characters. The display name shown everywhere. |
| **Description** | No | Up to 500 characters. A short summary; appears on the projects list. |
| **Slug** | Yes | Lowercase letters, numbers, and hyphens. Used in URLs (e.g. `/projects/acme-support`). Auto-generated from the name — edit if you want a shorter or different one. Must be unique across all projects. |
| **Prefix** | Yes | Uppercase letters and numbers, up to 10 characters. Used to build ticket IDs (e.g. `PROJ-123`). Auto-generated from the name. Must be unique across all projects. |
| **Template** | Yes | Determines the starting set of ticket types and statuses. See below. |

Slug and prefix are auto-filled as you type the name. You can override them, but choose carefully — they're harder to change later.

## Templates

Pick the template that most closely matches how you'll use the project. You can edit, add, and remove types and statuses afterward — the template just gives you sensible defaults.

### Software Development

For dev teams tracking bugs and features.

- **Types:** Bug, Feature, Task (default), Improvement
- **Statuses:** Open → In Progress → In Review → Resolved → Closed

### Customer Support

For help desks handling customer requests.

- **Types:** Question (default), Problem, Request
- **Statuses:** Open → In Progress → Waiting on Client → Resolved → Closed

### General

A minimal setup for simple task tracking.

- **Types:** Task (default), Issue
- **Statuses:** To Do → In Progress → Done

### Blank

No types, no statuses. You configure everything yourself in [Configure → Types & Statuses](/docs/configuring-a-project#types--statuses) before any tickets can be created.

## After creation

You're added as the project's admin and redirected to the new project's ticket list. The project is empty — your next steps:

1. Open **Configure** to review the types and statuses the template gave you
2. Add custom fields if you need structured data on tickets ([Custom fields](/docs/configuring-a-project#custom-fields))
3. Invite agents and clients ([Managing members](/docs/managing-members))
4. If external systems will submit tickets, set up [API clients](/docs/api-clients)
