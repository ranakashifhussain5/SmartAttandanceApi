# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **backend-only Laravel 12 REST API** for a university attendance system. Students are marked
present automatically when their phone detects a classroom's **BLE iBeacon** while inside a **GPS
geofence** — no frontend, no beacon firmware, this repo is the API only. The client is a separate
(not-yet-built) Flutter app.

**Read `PROJECT_CONTEXT.md` first for anything non-trivial** — it documents the domain model, the
full attendance-validation flow, API surface, and design history (why BLE+GPS replaced two earlier
approaches) in much more depth than is useful to repeat here. Keep it updated when architecture
changes. `project_handoff.md` has the hardware/firmware backstory. `project_api_spec.json`,
`hod.json`, and `teacher.json` at the repo root are **stale** (describe the old WiFi-MAC design) —
don't treat them as current ground truth.

## Commands

```bash
composer install                 # install deps
cp .env.example .env && php artisan key:generate

php artisan serve                 # dev server
php artisan migrate                # run migrations (SQLite locally, MySQL in prod)
php artisan db:seed                # DatabaseSeeder has a full realistic demo dataset

php artisan test                   # full suite
php artisan test --filter=test_forgot_password_verifies_teacher_email_with_employee_no
php artisan test tests/Feature/PasswordResetTest.php

vendor/bin/pint                    # code style (no custom pint.json — Laravel defaults)
vendor/bin/pint --test             # check without fixing
```

Tests run against **MySQL** (`phpunit.xml` sets `DB_CONNECTION=mysql`, `DB_DATABASE=test`), not the
SQLite used for local dev — a local MySQL server with an empty `test` database must exist to run
`php artisan test`. Feature tests use `RefreshDatabase`.

## Architecture

**Controllers are thin.** All real business logic lives in `app/Services/`. A controller validates
via a Form Request, delegates to a service, and returns a Resource. When changing behavior, look in
`app/Services/` first — e.g. `AttendanceService::mark()` and `SessionService` are the heart of the
attendance flow, not `AttendanceController`.

**Domain-rule failures throw `App\Exceptions\BusinessException`** (a message + HTTP status), not
generic exceptions — it self-renders as JSON, so no try/catch boilerplate is needed in controllers.
Rejected/suspicious attendance submissions are additionally logged to the `suspicious_attempts`
table (never silently dropped) rather than just returning an error.

**Authorization is layered two ways:**
- Coarse: route-level `role:` middleware (`App\Http\Middleware\EnsureUserHasRole`, aliased as
  `role`), e.g. `Route::middleware('role:teacher,hod')`. Checks `$user->role` against an allow-list.
- Fine-grained: Laravel Policies (`app/Policies/`) for object-level checks the role middleware can't
  express, e.g. "this HOD can only browse their own department" or "this teacher can only end a
  session for their own timetable slot."

**Domain hierarchy:** `Department → Program → ProgramCourse`, `Program → Batch → Student`,
`Timetable (Batch + ProgramCourse + Teacher + Room + Day + TimeSlot) → ClassSession → Attendance` /
`SuspiciousAttempt`. `is_hod` on `Teacher` is computed (true iff `Department.hod_teacher_id` points
to them), not a stored column.

**No queues, no scheduled tasks.** Despite queue tables existing, everything runs synchronously —
"notifications" are plain DB rows written inline, and sessions must be manually ended by a teacher
(no auto-expiry). Don't assume a job will run later; the request/response cycle is the whole thing.

**Pure JSON API**, enforced in `bootstrap/app.php`: `shouldRenderJsonWhen` forces JSON error
responses for `api/*`, and `redirectGuestsTo` returns `null` instead of trying to redirect
unauthenticated requests to a nonexistent login route. There are no Blade views, no JS build
pipeline, and `routes/web.php` only exposes a `GET /` health check.

**Config that changes runtime behavior:** `config/attendance.php` (`ATTENDANCE_CAMPUS_LAT/LNG`,
`ATTENDANCE_GEOFENCE_RADIUS`). If lat/lng are blank, the GPS geofence check is a **silent no-op** —
worth checking when debugging why "attendance marks from anywhere."

## Deployment

Docker (`php:8.3-cli`, no nginx/php-fpm — Laravel's built-in server directly), deployed via Coolify.
Entrypoint runs `php artisan migrate --force` then `php artisan serve --host=0.0.0.0 --port=8000`.
