# Oryxpace Core Plan

## Scope And Direction

Oryxpace should become the shared work system for Crew Center, Oryxie, OryxOps, and staff. The existing Laravel + Livewire migration is a viable foundation. Do not restart from zero. Stabilize the domain and authorization boundaries before adding every integration.

Terminology:

- **Department**: top-level operational area.
- **Project**: a work stream within a department. No deeper container hierarchy is required.
- **Task**: a unit of work, optionally with nested subtasks.
- **Resolution user**: the user/pilot the task is being resolved for. This is preferable to “target user” because it describes the operational relationship without implying assignment.
- **Assignee**: staff member responsible for doing the work. Multiple assignees are supported.

## Current State

### Implemented foundation

- Laravel 13, Livewire 4, Blade, Tailwind/Vite setup.
- Department/project structure with project dates and archive flag.
- Project Kanban columns with ordering, default column, editable names, and required permission field.
- Project tags with name, color, and permission field.
- Tasks with recursive `parent_id`, multiple pilot assignees, resolution user, due date, and column ordering.
- Task activity log model and log entries for several mutations.
- Task custom fields and task values.
- Schedule records with cron text, subtask template JSON, active state, and manual run.
- Project subscriptions and notification preference tables.
- API key middleware, generic task endpoints, and application/recruitment pipeline endpoints.
- Livewire Kanban, list, task detail modal, schedule manager, and notification settings pages.
- Feature tests covering recursive loading, API key protection, and application/recruitment webhooks.
- Pilot-backed session authentication for Livewire and JWT bearer authentication at `/api/auth/login` and `/api/auth/me` (with legacy root aliases during the current route transition).

### Incomplete or unsafe foundation

- The recursive eager-loading relation is unbounded and may issue excessive queries or recurse indefinitely on malformed/deep data. Add depth limits or a database/tree strategy and indexes before relying on it at scale.
- Status updates do not consistently verify that the destination column belongs to the task’s project.
- API and Livewire mutations are not yet unified behind shared authorization, activity, notification, and broadcast logic.
- API identity is currently an API key, but external object IDs/idempotency and scoped abilities need a defined contract.
- Schedule creation exists, but there is no verified scheduler/queue execution path for cron schedules.
- Notification delivery currently has a Discord webhook implementation, not complete per-user Discord/email preferences and delivery jobs.
- Cross-session real-time broadcasting is not wired. Livewire component refreshes alone are not sufficient for all users seeing changes.
- Project progress calculation, archive UX, column reordering, tag management, custom permission management, and complete task editing need product-ready workflows.
- The root `README.md` still contains the default Laravel skeleton text and should be replaced with project setup documentation.
- Authentication currently has no refresh/revocation mechanism; JWT secret configuration and deployment policy still need to be finalized.

## Delivery Phases

### Phase 0: Baseline And Decisions

- [ ] Confirm the current Laravel migration is the canonical application.
- [ ] Replace stale setup documentation and document local database/auth assumptions.
- [ ] Define the Pilot/user relationship shared with Crew Center, including stable external IDs.
- [ ] Choose the real-time broadcaster, preferably Laravel Reverb for a self-hosted Laravel deployment, and define production hosting constraints.
- [ ] Add baseline factories/seeders and feature-test conventions.
- [ ] Confirm the exact shared `pilots`, `permissions`, and `awards_granted` schemas against the live database before expanding profile joins.
- [ ] Decide JWT refresh/revocation strategy without adding tables to the shared production schema.

### Phase 1: Core Task Domain

- [ ] Normalize task status semantics around project-owned Kanban columns.
- [ ] Add indexes for `tasks(project_id, parent_id, kanban_column_id, order_in_column)` and integration lookup keys.
- [ ] Decide recursive storage strategy: retain adjacency list with bounded-depth reads for normal UI, and use a recursive query/materialized path only if reporting or deep trees require it.
- [ ] Prevent cycles, cross-project parents, and invalid column/project combinations.
- [ ] Add a shared task mutation service/action for create, edit, assign, status move, reorder, tag, custom field, and archive-related operations.
- [ ] Ensure every mutation creates a structured activity event with actor, source, old value, new value, and timestamp.
- [ ] Add task detail history UI, comments/notes if required, and reliable subtask completion roll-up rules.

### Phase 2: Project Workflows And Permissions

- [ ] Finish project list/kanban switching, filtering, sorting, pagination, and responsive behavior.
- [ ] Add project progress calculation and explicit completion-date behavior.
- [ ] Add archive/unarchive workflows and archived-project filtering.
- [ ] Support column create/edit/delete/reorder, one default column, and safe status transitions.
- [ ] Replace string-only permission checks with named custom permissions and policy-backed transition authorization.
- [ ] Add project tag CRUD, color validation, permission rules, and task tag editing.
- [ ] Add custom field definition/template CRUD with supported types, options, validation, and project scoping.

### Phase 3: Real-Time And Notifications

- [ ] Define domain events such as `TaskCreated`, `TaskUpdated`, `TaskMoved`, `TaskAssigned`, `TaskCommented`, and `TaskActivityRecorded`.
- [ ] Broadcast project-scoped events through Reverb/the selected broadcaster and authorize private project channels.
- [ ] Make Livewire components react to broadcasts and refresh only affected data; avoid broad page polling.
- [ ] Queue notification delivery and retries.
- [ ] Implement user preferences for assigned-task updates, project subscriptions, status changes, mentions, and digest/announcement options.
- [ ] Add Discord bot delivery through Oryxie and email delivery through the selected mail provider. Every notification includes a direct task link.
- [ ] Deduplicate recipients when a user is both assigned and subscribed.

### Phase 4: Templates And Scheduling

- [ ] Introduce reusable task templates with title, description, default column, assignees, tags, custom fields, and nested subtask definitions.
- [ ] Keep schedule definitions separate from task templates; a schedule instantiates a template at a due time.
- [ ] Validate cron expressions, define timezone behavior, and run schedules through Laravel’s scheduler and queued jobs.
- [ ] Make schedule runs idempotent and record run history, failures, and generated task IDs.
- [ ] Support manual run, pause/resume, next-run preview, and safe template editing.

### Phase 5: Stable Integration API

- [ ] Publish versioned API documentation and consistent error/response formats.
- [ ] Scope API keys by integration and ability; hash stored keys where operationally possible and provide rotation/revocation.
- [ ] Require idempotency keys and persist external source/type/ID mappings.
- [ ] Add endpoints for task updates, assignment, status changes, comments/activity, and linking external records.
- [ ] Emit outbound webhook events for integrations that need status updates instead of requiring polling.
- [ ] Add rate limits, request logging, correlation IDs, and replay-safe processing.

## Integration Pipelines

Implement these on top of the stable API and shared task actions. Each pipeline must define its external identifier, source of truth, retry behavior, and status mapping.

### Crew Center

- [ ] Applications create an application task with a CC application link and external ID.
- [ ] CC status changes update the mapped task, with explicit mapping rather than assuming identical custom statuses.
- [ ] Recruitment links the Discord candidate and later CC account to the same task; decide whether one-click approval is safe before automating it.
- [ ] Training can remain a parent task with review subtasks: written test, CR1/CR2 replay reviews, messages, and follow-up work.
- [ ] Passed written tests create the next task/subtask and append activity automatically.
- [ ] Replay submission creates a linked review subtask; CC review completion updates it.
- [ ] Failed checkrides create a new attempt subtask, preserving prior attempt history.
- [ ] Define how IFATC members are detected, preferably by a trusted CC/Oryxie source rather than an unverified client flag.
- [ ] Pirep flags create linked review tasks with the CC/Oryxie record ID.

### Oryxie Discord Bot

- [ ] Discord ticket open/close events create/update a linked task.
- [ ] Preserve ticket ID and channel URL as external identity/link fields; do not locate tickets by title text.
- [ ] Add creator as the resolution user and optionally assign staff using an explicit routing rule.
- [ ] Send task status/activity updates through the bot according to preferences.

### OryxOps And Other Services

- [ ] Use the same versioned API and idempotency model for training, operations, landing-page, and future integrations.
- [ ] Defer deep GitHub/Instagram/IF Live automation until a concrete workflow and measurable benefit are defined.

## Later Product Areas

Add these after the core workflow is reliable:

- [ ] Recurring internal/external event planning tasks with subtasks for threads, graphics, posting, and follow-up.
- [ ] Recurring social tasks such as POTW/LOTW, POTM, posts, and thread bumps.
- [ ] Route renewals and lookup work.
- [ ] Cargo and OryxOps training workflows.
- [ ] Optional outbound posting/commenting integrations after permissions and audit requirements are clear.
- [ ] Reporting, dashboards, workload views, SLA/due-date reminders, and project-level activity feeds.

## Non-Goals For The First Release

- Arbitrarily deep project/container hierarchies.
- A fully generic workflow engine before the task model is proven.
- Automatic approval actions that change Crew Center account state without an auditable confirmation boundary.
- Building every external integration before the API contract and idempotency behavior are stable.
- Treating custom external statuses as native Kanban statuses; integrations should use explicit mappings.

## Quality Gates

- [ ] Feature tests cover authorization, cross-project/cycle validation, idempotent webhooks, status transitions, activity history, notifications, and schedule retries.
- [ ] No repeated relationship queries in Kanban/list/detail rendering; verify with query-count tests where useful.
- [ ] Broadcast authorization and event payloads contain no secrets or unnecessary personal data.
- [ ] Queue failures and integration retries are observable.
- [ ] Run `php artisan test`, `npm run build`, and code formatting/static checks before merging.
