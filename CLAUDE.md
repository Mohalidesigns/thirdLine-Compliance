# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

Fresh Laravel 13 skeleton (PHP 8.3+) intended to become a compliance solution. The business requirements live in `Compliance Solution/NGCOMPLY_BRD_v1.0.docx` — read it (via the docx skill) before designing features, since almost no domain code has been written yet. The only model is the default `User`, the only route is the welcome page, and `Controllers/` contains only the base `Controller.php`.

## Commands

- `composer dev` — runs `php artisan serve`, `queue:listen`, `pail` (log tail), and `npm run dev` (Vite) concurrently. This is the standard local dev command; do not start the pieces individually unless debugging one of them.
- `composer test` — clears config then runs `php artisan test`. Use this rather than calling phpunit directly, otherwise stale config can cause confusing failures.
- `php artisan test --filter=TestName` — run a single test class or method.
- `vendor/bin/pint` — Laravel Pint formatter (configured via Pint defaults; no custom `pint.json`).
- `php artisan migrate` — apply migrations. `.env` points at MySQL database `compliancesol` on `127.0.0.1`, but `phpunit.xml` overrides the test suite to use an in-memory SQLite database, so tests do not touch MySQL.
- `composer setup` — one-shot install: composer install, copy `.env`, key:generate, migrate, npm install, build.

## Architecture notes

- **Laravel 13 streamlined bootstrap.** Configuration is centralized in `bootstrap/app.php` (the `Application::configure(...)` chain) rather than the legacy `App\Http\Kernel` / `App\Console\Kernel`. Register middleware in `withMiddleware()`, exception handlers in `withExceptions()`, and the scheduler in `routes/console.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php` — do not recreate them.
- **No `routes/api.php` by default.** Only `routes/web.php` and `routes/console.php` are wired in `bootstrap/app.php`. If API routes are needed, run `php artisan install:api` (which adds Sanctum and the api route file) rather than hand-creating the file.
- **Health endpoint** is exposed at `/up` via the `health:` argument in `bootstrap/app.php`.
- **Queues, cache, and sessions all default to the database driver** in `.env` (not file/redis). New features that use queues/cache/sessions therefore require the migrations in `database/migrations/0001_01_01_000001_create_cache_table.php` and `0001_01_01_000002_create_jobs_table.php` to have been run.
- **Frontend** is Vite + Tailwind v4 via `@tailwindcss/vite` (no `tailwind.config.js` — Tailwind v4 is CSS-first, configured inside `resources/css/app.css`). Blade is the templating layer; there is no Inertia/Livewire/React setup.

## Conventions

- PSR-4 autoload: `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`, `Tests\` → `tests/`.
- PHPUnit (not Pest) — `tests/Unit` and `tests/Feature` directories; `Tests\TestCase` extends `Illuminate\Foundation\Testing\TestCase`.
