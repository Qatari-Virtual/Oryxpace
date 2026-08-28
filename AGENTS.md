# Oryxpace Agent Guide

## Project

Oryxpace (OP) is a Laravel application for internal operational work management. It organizes work as `Departments -> Projects -> Tasks`, with recursive subtasks, Kanban/list views, integrations, automation, and task history.

The repository was recently migrated from an older Django/React layout. The active application is Laravel; references to Django, React, or a separate `backend/` and `frontend/` tree are historical and should not be used for new work.

## Stack

- PHP 8.3+
- Laravel 13
- Livewire 4
- Blade and Tailwind CSS 4 via Vite
- SQLite is suitable for local development; deployment may use the configured relational database
- PHPUnit/Laravel test runner

## Important Paths

- `app/Models/` - Eloquent domain models
- `app/Livewire/` - interactive server-rendered components
- `app/Http/Controllers/Api/` - integration API controllers
- `app/Http/Middleware/ApiKeyMiddleware.php` - API key authentication
- `app/Services/NotificationService.php` - external task notifications
- `database/migrations/` - schema and relationships
- `resources/views/livewire/` - Livewire views
- `routes/web.php` - browser routes and Livewire pages
- `routes/api.php` - API and pipeline routes
- `tests/Feature/` - feature coverage for task and pipeline behavior
- `PLAN.md` - product scope, implementation phases, and open decisions

## Current Domain

- `Department` has many `Project` records.
- `Project` owns Kanban columns, tags, tasks, custom fields, schedules, and subscribers.
- `Task` uses `parent_id` for subtasks, supports multiple `Pilot` assignees, a resolution user, tags, custom field values, and activity logs.
- `Pilot` is the authenticatable user model and maps to the shared `pilots` table. Do not create a second application user system.
- Kanban columns represent task status. Column permissions currently gate moves into a column.
- API keys protect task and pipeline endpoints used by Crew Center and Oryxie.

## Development

```bash
composer install
npm install
php artisan migrate
php artisan serve
npm run dev
php artisan test
npm run build
```

Use `composer run dev` when the project-level development process is preferred. Do not commit `.env`, credentials, generated logs, or build output unless explicitly required.

## Conventions

- Use Eloquent relationships and explicit eager loading. Avoid queries from Blade templates and Livewire loops.
- Keep task mutations in application services/actions as the domain grows so activity logging, authorization, notifications, and broadcasts cannot be skipped.
- Treat every task mutation as an auditable event. Activity history is user-facing, not just diagnostic logging.
- Use policies/permissions for authorization; do not rely on hidden UI controls.
- Validate integration payloads and make webhook operations idempotent using external identifiers.
- Keep Livewire for interactive page state. Use Laravel broadcasting/Reverb (or the selected compatible broadcaster) for cross-session real-time updates; do not treat polling as the primary real-time design.
- Do not reintroduce the old Django/React structure without an explicit architectural decision.
- Do not add production migrations that alter shared Oryxie/OryxOps tables without an explicit rollout plan. Local/test migrations may create representative tables.
