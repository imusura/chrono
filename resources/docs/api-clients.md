# API clients

API clients are tokens that let external systems create tickets in a project without using a user account. Examples: a website contact form, an email-to-ticket pipe, a monitoring system that opens tickets on alerts.

Manage them under **Configure → Integrations** (admin only).

For the consumer-facing reference — endpoints, request format, error codes — see the [Integrations API](/docs/integrations-api).

## Creating a client

Click **New API client**. You'll need:

- **Name** — what this token is for. Shown in the integrations list and on every ticket the token creates. Pick something recognizable, like `marketing-site-form` or `pagerduty-webhook`.
- **Default ticket type** — every ticket the token creates will be of this type. The token can't choose a type per request, so pick whichever type fits the source. The default type must be **active** at the time you create the client.

After clicking **Create**, the token is shown **once** in a dialog: a string that looks like `tkt_` followed by 32 random characters. Copy it now and store it somewhere safe (a password manager, a secrets vault, your CI environment).

**The token is not retrievable later.** If you lose it, you have to rotate it — see below.

## What's shown in the list

For each client:

- Name (click to edit)
- The default ticket type
- An **Active/Disabled** switch
- **Last used** — when the token last successfully created a ticket, or "Never used"

## Editing

Click the client's name to change its display name or default ticket type. Existing tickets the client already created keep the type they had — only future submissions are affected.

## Rotating a token

Click the rotate button (refresh icon). A new token is generated and shown to you in the same one-time-display dialog. **The old token stops working immediately** — update wherever the old one was stored before rotating.

Rotate when:

- A token might have leaked
- You want to enforce a rotation policy
- You're handing off ownership of an integration

## Disabling vs deleting

The **Active** switch is a soft kill: the token stays in the list, but any request using it returns 403 until you flip it back on. Use this for temporary suspension — a misbehaving integration, or while you investigate suspicious traffic.

The **Delete** button removes the client. The token stops working, and the client disappears from the list. Tickets it previously created stay in the project and continue to show the client's name in their submission context.

## Security notes

- **Never embed a token in browser-side code.** Calls to the integrations API must come from your server. CORS is not enabled on integration endpoints; a browser request will fail anyway, but don't put the token in client-side code in the first place.
- **One token per integration.** Don't share a token across multiple systems — if one needs rotating, you have to rotate them all.
- **Tokens are project-scoped.** They can only create tickets in the project where they were issued. They can't read tickets, list members, or hit any other endpoint.
