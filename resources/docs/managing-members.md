# Managing members

Open **Configure → Members**. Only project admins see this tab.

The panel lists every member with their avatar, name, email, and current role.

## Adding a member

Click **Add Member**. Enter:

- **Email address** — must match an existing user account in the system. Members are not invited via email; the user must already exist.
- **Role** — Admin, Agent, or Client. See [Roles](/docs/roles) for what each can do.

If no user exists with that email, the request fails. The user has to be created first by someone with that ability.

## Changing a role

Use the role dropdown next to a member. Changes take effect immediately. You **cannot change your own role** — the dropdown is disabled for the current user. Have another admin do it if needed.

## Removing a member

Click the trash icon next to a member. They lose access to the project immediately. You **cannot remove yourself** — have another admin do it.

Removing a member doesn't delete tickets they created or commented on; their name continues to appear in history.

## Default assignee

Each project can have a **default assignee** — the person new tickets are automatically assigned to when they're created.

In the members list, agent and admin rows have a **star icon** next to them. Click the star to set that member as the default assignee. Click it again to clear it. Only one member can be the default at a time.

The star doesn't appear for clients — it only makes sense for members who handle tickets.

If you demote the current default assignee to the client role (or remove them from the project), the default is automatically cleared.

## Choosing the right role

- **Admin** — give sparingly. Anyone who needs to configure the project or manage other members.
- **Agent** — anyone on your team who works tickets day-to-day.
- **Client** — external users (or internal users acting in a customer capacity) who only see their own tickets.

A client added by mistake who should have been an agent will see almost nothing in the project until you change their role.
