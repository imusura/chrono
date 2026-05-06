# Overview

TickIt is a project-based ticketing system. Each project is its own workspace with its own ticket types, statuses, custom fields, members, and integrations. Users belong to one or more projects, and their permissions are scoped to that project.

## Core ideas

- **Projects are tenants.** Settings, members, and tickets do not bleed across projects. Adding someone to one project gives them no access to any other project.
- **Roles are per project.** The same user can be an admin in one project and a client in another. There is no global "agent" role — it's always tied to a specific project.
- **Tickets carry rich context.** Every ticket has a type, status, priority, description (rich text), comments, attachments, and optional custom fields defined by the project's admin.
- **External systems can submit tickets.** Each project can issue API tokens that let external apps create tickets without a user account. See [API clients](/docs/api-clients).

## Where to start

If you're new to the app, the next thing to read is [Roles](/docs/roles) — the rest of the docs assume you know which role you're operating as.

## Common workflows

- **An admin sets up a project** — picks a template, configures types and statuses, invites members. See [Creating a project](/docs/creating-a-project) and [Configuring a project](/docs/configuring-a-project).
- **An agent works tickets day-to-day** — uses the [list view](/docs/list-view) for triage and the [Kanban board](/docs/kanban-board) for flow.
- **A client submits and tracks their own tickets** — they only see what they created.
- **An external app pushes tickets in** — via the [Integrations API](/docs/integrations-api), authenticated with a token issued in [API clients](/docs/api-clients).
