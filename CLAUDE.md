# Ticketing System

## Project
- **Repo:** https://github.com/imusura/ticketing
- **Local URL:** http://ticketing.test (Laravel Herd)
- **Vite Dev Server:** http://localhost:5173

## Related projects
- **household** (`../household/`, `http://household.test`) — primary integrator; sends feedback tickets via this app's API.
- **prices** (`../prices/`, `http://prices.test`) — sibling project, same Laravel + Vue 3 stack family.

## Docs
- **Subsystem docs:** `../stack-docs/apps/ticketing/` — one file per subsystem
- **Shared conventions:** `../stack-docs/conventions.md` — patterns common across all three apps
- **Integration map:** `../stack-docs/integration.md` — cross-app API contracts and auth flow
- **Tech-debt tracker:** `../stack-docs/tech-debt.md` — smells found across all apps (App column scopes entries)

## Development Environment
- Laravel Herd serving from d:\Projects
- MariaDB (local)
- TablePlus for database GUI
- VS Code

## Skills
- **frontend-design** (anthropics/skills) — UI design guidance
- **vue-pinia-best-practices** (vuejs-ai/skills) — Pinia store patterns
- **laravel-specialist** (jeffallan/claude-skills) — Laravel API, Eloquent, Sanctum patterns

Consult skills automatically when working in their domain. Read the SKILL.md files in .agents/skills/ before building relevant features.

## Stack
- **Backend:** Laravel API with Sanctum auth
- **Frontend:** Vue 3 SPA with TypeScript
- **Server State:** TanStack Query (caching, refetching, mutations)
- **Client State:** Pinia (auth, UI preferences — not for server data)
- **Data Table:** TanStack Table (server-side sorting, filtering, pagination)
- **Routing:** Vue Router
- **HTTP:** Axios
- **UI:** shadcn-vue + Tailwind CSS
- **Toasts:** Sonner (vue-sonner) — global error notifications via httpClient interceptors
- **Editor:** Tiptap (WYSIWYG for tickets and comments)
- **Validation:** Form Requests
- **API Responses:** API Resources
- **Database:** MariaDB
- **Build:** Vite

## Architecture
- Laravel serves as a JSON API only — no Blade views except the SPA shell
- Vue app lives in resources/js/
- API routes in routes/api.php — no web routes except the SPA catch-all

## Frontend Structure
```
resources/js/
├── main.ts
├── httpClient.ts
├── App.vue
├── router/
├── stores/              # Pinia — client state only (auth, UI)
├── services/            # Axios API call layer
├── composables/         # TanStack Query wrappers, URL filter sync
├── views/
│   ├── auth/
│   ├── tickets/
│   ├── client/
│   ├── agent/
│   └── admin/
├── components/
│   ├── ui/              # shadcn-vue primitives
│   ├── data-table/      # Reusable table parts (pagination, sort headers, filter chips)
│   ├── tickets/         # Ticket-specific components
│   └── layout/          # App shell, sidebar, user menu
├── types/
└── lib/                 # Shared constants (ticket config, utils)
```

## Roles
- **Client** — submits and tracks their own tickets only
- **Agent** — handles any ticket on the project (not limited to assigned)
- **Admin** — full access, user management, system settings

## Ticket Schema
- Types: Bug, Feature, Task, Question
- Statuses: Open, In Progress, Waiting on Client, Resolved, Closed
- Priorities: Low, Medium, High, Urgent
- Content uses rich text (Tiptap)

## Edit Permissions
- **Client** — can edit tickets they created
- **Agent** — can view and edit any ticket on the project
- **Admin** — can edit any ticket and manage project config

## Conventions

### PHP / Laravel
- Use Laravel Pint for code formatting
- Use Form Requests for validation
- Use API Resources for response transformation
- TypeScript types are maintained manually in resources/js/types/ — keep them in sync with models
- Controllers should be thin — business logic goes in service classes
- Name controllers with the Resource + Controller suffix (e.g. TicketController)
- Use PHP backed enums for fixed values (roles, statuses, priorities)

### TypeScript / Vue
- Always use TypeScript — no .js files, no lang="ts" omissions, no `any` types unless absolutely unavoidable
- Use TypeScript strict mode
- Use Composition API with `<script setup lang="ts">` — no Options API
- Keep components small and focused — break large components into smaller ones, no 600-line files
- Use arrow functions, not function declarations
- Use TanStack Query for server state (queries + mutations with cache invalidation) — no Pinia stores for API data
- Use Pinia only for client-side state (auth, UI preferences) — no prop drilling beyond 2 levels
- API calls go in services/ — components never call Axios directly
- Shared constants (status/priority config, labels) go in lib/ — no duplicating config across components
- Types/interfaces go in types/ — no inline type definitions for models
- Views should not import from or depend on the HTTP client (e.g. Axios) directly — use typed errors from types/
- HTTP error handling goes in httpClient interceptors — views only handle 422 validation errors (per-field display); all other errors (429, 500, network) are handled globally via Sonner toast

### General
- No comments unless the logic is non-obvious
- No unused imports or variables
- Commits follow the phased plan — one phase per commit
