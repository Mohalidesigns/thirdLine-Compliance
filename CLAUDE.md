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

## Authorization (Phase 4C)

Role-based access control is implemented via `spatie/laravel-permission` (plain mode, no teams). Multi-tenant scoping still happens at the data layer via `BelongsToTenant`.

### Roles

| Role | Admin Panel | Notes |
|---|---|---|
| `super_admin` | Yes | Bypasses all checks via `Gate::before` |
| `compliance_officer` | Yes | Full CRUD on all domain models |
| `risk_owner` | No | CRUD on own risks; cycle must be in data_capture or scoring to edit |
| `control_tester` | No | Can record tests and manage issues; read-only on controls/risks |
| `policy_owner` | No | Can draft and submit policies; cannot approve (separation of duties) |
| `auditor` | Yes (read-only) | Read-only across all modules |

### Where permissions live

`database/seeders/RolesAndPermissionsSeeder.php` is the single source of truth for the permission matrix. It is called first in `DatabaseSeeder::run()`.

### How to add a new permission

1. Add the permission name to the `$permissions` array in `RolesAndPermissionsSeeder`.
2. Add it to the appropriate roles in `$rolePermissions`.
3. Create or update the corresponding Policy class in `app/Policies/` to check `$user->can('resource.verb')`.
4. Call `$this->authorize(...)` in the relevant controller action.

### How to write a test for a role-gated action

```php
uses(RefreshDatabase::class);

beforeEach(fn () => (new RolesAndPermissionsSeeder)->run());

it('wrong role gets 403', function () {
    $user = User::factory()->create()->assignRole('auditor');
    $this->actingAs($user)->post('/controls', [...])-> assertForbidden();
});
```

Use `$this->actingAsRole('role_name')` (defined in `Tests\TestCase`) to authenticate and return `$this` for chaining.

### Key files

- `app/Policies/` — one Policy class per domain model
- `app/Providers/AppServiceProvider.php` — `Gate::before` (super_admin bypass) + policy registrations
- `app/Http/Controllers/Controller.php` — base controller with `AuthorizesRequests` trait
- `phpunit.xml` — sets `memory_limit = 512M` for the test suite

## Conventions

- PSR-4 autoload: `App\` → `app/`, `Database\Factories\` → `database/factories/`, `Database\Seeders\` → `database/seeders/`, `Tests\` → `tests/`.
- PHPUnit (not Pest) — `tests/Unit` and `tests/Feature` directories; `Tests\TestCase` extends `Illuminate\Foundation\Testing\TestCase`.
