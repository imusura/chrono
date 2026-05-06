# Integrations API (v1)

External apps can submit tickets into a project using a project-scoped API token. Tokens are issued by project admins under **Project Settings → Integrations**.

## Authentication

Send the token as a Bearer header on every request:

```
Authorization: Bearer tkt_<32 random chars>
```

Tokens are scoped to a single project. They cannot be used to read tickets, list members, or call any other endpoint.

**Never embed tokens in browser-side code.** Calls must originate from your server. CORS is not configured for `/api/integrations/*`.

## Rate limit

60 requests per minute, per token. Exceeding the limit returns `429 Too Many Requests`.

## Create a ticket

```
POST /api/integrations/v1/tickets
Content-Type: application/json
Authorization: Bearer tkt_...
```

### Request body

| Field             | Type     | Required | Notes                                                                                  |
|-------------------|----------|----------|----------------------------------------------------------------------------------------|
| `subject`         | string   | yes      | Max 255 chars. Becomes the ticket title.                                               |
| `description`     | string   | yes      | Body / first message. Plain text or HTML (rendered in the ticket UI).                  |
| `submitter_email` | string   | no       | Email of the person submitting (your end user, not the integration owner).             |
| `submitter_name`  | string   | no       | Display name of the submitter.                                                         |
| `metadata`        | object   | no       | Arbitrary debug context. Max 32 keys, each value scalar/string ≤1KB, total ≤4KB.       |

The ticket type and initial status are determined by the API client's configured **default ticket type** — set when the token was issued.

### Idempotency

Pass an `Idempotency-Key` header to make retries safe:

```
Idempotency-Key: feedback-form-2026-04-13-abc123
```

- Format: `^[A-Za-z0-9_\-]{8,128}$` (alphanumerics, underscore, hyphen, 8–128 chars).
- A given `(api_client, idempotency_key)` pair is a no-op on retries. Replays succeed indefinitely — there is no expiry.
- Replays return `201 Created` (not `200`), so consumers do not need to special-case retries.

### Success response

```
HTTP/1.1 201 Created
```

Empty body. The `201` status code is the entire signal — the ticket was created (or, for an idempotent retry, already existed). Consumers don't get the ticket id back; the ticketing app owns the ticket lifecycle from that point on.

### Error responses

| Status | Cause                                                          | Body                                                                |
|--------|----------------------------------------------------------------|---------------------------------------------------------------------|
| 401    | Missing, malformed, unknown, or revoked token                  | `{"message": "Unauthenticated."}`                                   |
| 403    | Token belongs to a disabled (deactivated) API client           | `{"message": "This API client is disabled."}`                       |
| 422    | Validation failure (missing fields, oversized metadata, etc.)  | `{"message": "...", "errors": {"<field>": ["..."]}}`                |
| 429    | Rate limit exceeded                                            | Standard Laravel throttle response                                  |
| 500    | Unexpected server error                                        | Generic error                                                       |

## Example: curl

```bash
curl -i -X POST https://ticketing.example.com/api/integrations/v1/tickets \
  -H "Authorization: Bearer tkt_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: feedback-2026-04-13-abc123" \
  -d '{
    "subject": "Cannot save my profile",
    "description": "<p>Save button does nothing on the profile page.</p>",
    "submitter_email": "user@example.com",
    "submitter_name": "Jane Doe",
    "metadata": {
      "page": "/account/profile",
      "app_version": "1.4.2",
      "browser": "Chrome 124"
    }
  }'
```

## Versioning

This document describes **v1**. Breaking changes will land under `/api/integrations/v2/...` and v1 will continue to work.

## Token lifecycle (admin)

- Created from **Project Settings → Integrations**. Plaintext token shown once at creation; only the SHA-256 hash is stored.
- **Rotate** generates a new token and immediately invalidates the previous one.
- **Deactivate** (toggle `is_active`) blocks the token without losing the audit trail.
- **Delete** soft-deletes the client. Existing tickets keep their `created_via_api_client_id` link so their origin remains visible.

## Logging

Every authenticated request is logged to `storage/logs/integrations-api-*.log` with the API client id, project id, response status, ticket id (on success), and an idempotency replay flag. Tokens are never logged.
