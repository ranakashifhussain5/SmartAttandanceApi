# Project Context — University Attendance System (BLE + GPS)

> Last updated: 2026-07-14
> This file is a living reference describing what this project is, the tech
> stack in use, the domain model, and how the whole system actually works
> end-to-end. Keep it updated when the architecture changes.

---

## 1. What this project is

A **backend-only REST API** for a university/college **class attendance
system**. Attendance is captured automatically when a student's phone
detects a physical **BLE beacon** installed in a classroom while also being
inside a **GPS geofence** around campus.

- There is **no frontend in this repository**. The client is a (not yet
  built) **Flutter mobile app** that lives in a separate project.
- There is **no beacon firmware in this repository** either — the ESP32
  firmware source was deliberately removed after the physical beacons were
  flashed. See `project_handoff.md` (root) for the full hardware story.
- This repo is purely the **Laravel API** that the mobile app (student side)
  and, eventually, an admin panel talk to.

### Real-world flow

1. Each classroom has an **ESP32-S3 BLE beacon** glued to the wall,
   broadcasting a passive **iBeacon** advertisement: a fixed **UUID** +
   **Major** (= room number) + unused Minor, at 0 dBm TX power (~20–30m
   range).
2. A teacher starts a class session from the app → `POST
   /api/sessions/{timetable}/start`.
3. Every student in that batch gets an `absent` attendance row pre-created,
   plus a push-style notification.
4. The student's phone: checks it's inside the **campus GPS geofence** →
   passively scans BLE → reads `{uuid, major, rssi}` from the classroom
   beacon → submits `POST /api/attendance/mark` with
   `{session_id, detected_uuid, detected_major, rssi, latitude, longitude}`.
5. The API validates GPS distance, RSSI strength, and beacon identity, then
   flips that student's attendance row to `present`.
6. Any failed/suspicious submission is logged to a `suspicious_attempts`
   table for anti-cheating audit (never silently dropped).
7. Teacher ends the session → still-absent students get notified, teacher
   gets a present/absent summary.

This design **replaced an earlier WiFi-MAC-address-based approach** (and
before that, a rotating-HMAC-token approach) — both abandoned because the
ESP32 beacons have no RTC/WiFi to stay time-synced with the server. See
"Design history" below.

---

## 2. Tech stack / tools

| Layer | Technology |
|---|---|
| Language / framework | PHP 8.2, **Laravel 12** |
| API style | Pure JSON REST (no Blade views, no Livewire/Inertia/Vue/React) |
| Auth | **Laravel Sanctum** (Bearer personal access tokens, no cookie/session auth) |
| Database (local/dev) | SQLite (`DB_CONNECTION=sqlite` in `.env.example`) |
| Database (production) | MySQL (per `project_handoff.md` / Docker setup) |
| Session / Cache / Queue driver | `database` driver for all three (no Redis actively used, though `phpredis` client is present in `.env.example` as an unused option) |
| Authorization | Custom `role` middleware (admin / hod / teacher / student) **+** Laravel Policies for object-level checks |
| Background jobs | **None in use** — `database` queue tables exist but no `app/Jobs`, no dispatches; all "notifications" are synchronous DB writes |
| Scheduled tasks | **None** — no `app/Console/Commands`, no `Schedule::` calls (e.g. sessions are never auto-ended, only teacher-triggered) |
| Testing | PHPUnit (`phpunit.xml`) — currently only the Laravel-default example tests remain; **no real domain tests exist yet** |
| Dev tooling | Laravel Pint (code style), Laravel Sail, Laravel Pail, Mockery, Collision, Faker |
| Deployment | **Docker** — `php:8.3-cli` base image, `composer install --no-dev`, entrypoint runs `php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000` (no nginx/php-fpm — Laravel's built-in server directly). Deployed via **Coolify** per notes in `project_api_spec.json`. |
| Frontend (out of repo) | Flutter mobile app (not yet started) |
| Hardware (out of repo) | ESP32-S3, ESP-IDF v5, Bluedroid BLE stack, WS2812B RGB status LED — iBeacon advertiser |

No JS build pipeline exists at all (no `package.json`, `vite.config.js`,
`tailwind.config.js`) — confirmed absent from the repo.

---

## 3. Domain model (database schema)

**Academic hierarchy:**

```
Department ──< Program ──< ProgramCourse
     │              │
     │              └──< Batch ──< Student
     └──< Teacher
```

**Scheduling & attendance:**

```
Timetable (Batch + ProgramCourse + Teacher + Room + Day + TimeSlot)
     └──< ClassSession (one instance per day the teacher starts it)
              ├──< Attendance (one row per student in the batch)
              └──< SuspiciousAttempt (rejected submissions, audit trail)
```

### Key tables / models

- **User** — `role` enum (admin/hod/teacher/student), `status` (active/inactive). Uses Sanctum's `HasApiTokens`.
- **Teacher** — linked 1:1 to `User`; optionally linked to a `Department`. `is_hod` is computed (true if `Department.hod_teacher_id` points to this teacher), not a stored flag.
- **Student** — linked 1:1 to `User`; belongs to `Department` + `Batch`; has `is_blocked` (a teacher can block a student from marking attendance) and a computed `attendancePercentage`.
- **Department** — has one HOD (a `Teacher`), many teachers/students/programs.
- **Program** → **ProgramCourse** (courses) and **Batch** (cohorts, e.g. year/semester/shift).
- **Room** — `room_no`, **`beacon_major`** (unique per room — the iBeacon Major value), **`beacon_uuid`**, **`rssi_threshold`** (default -75 dBm). *(Originally had `wifi_name`/`wifi_mac` — replaced by BLE fields, see migration history below.)*
- **TimeSlot** — `start_time`/`end_time`.
- **Timetable** — the weekly recurring schedule: which batch, course, teacher, room, day, and slot.
- **ClassSession** (table `class_sessions`, deliberately not named `sessions` to avoid clashing with Laravel's own session table) — a concrete instance of a `Timetable` on a specific date, `status` = active/ended.
- **Attendance** (table `attendance`) — one per (session, student). Stores what the student's phone actually detected: `detected_uuid`, `detected_major`, `rssi`, `latitude`, `longitude`, plus `status` (present/absent) and `marked_at`.
- **SuspiciousAttempt** — append-only log of rejected attendance attempts, with a `fail_reason` and a JSON `payload` for forensic review.
- **Notification** — a custom, simple DB-backed notification (**not** Laravel's polymorphic notifications system) with `type` (attendance_started, attendance_marked, session_ended, student_blocked, student_unblocked).
- **AuditLog** — generic action log (who did what, old/new values, IP, user agent).

### Migration history highlights

- The rooms/attendance tables were **originally WiFi-MAC based**
  (`wifi_name`/`wifi_mac`, `wifi_mac_detected`).
- `2026_07_11_120000_replace_wifi_with_ble_gps_on_rooms_table` and
  `2026_07_11_120001_replace_wifi_with_ble_gps_on_attendance_table` are the
  **pivotal migrations** that converted the whole system to BLE beacon + GPS.
- `2026_07_11_120002_create_suspicious_attempts_table` added the anti-cheat
  audit log.
- `2026_07_14_073712_add_beacon_uuid_to_rooms_table` is a small **defensive
  fix-up migration** (`if (!Schema::hasColumn(...))`) — guards against an
  environment where the BLE migration didn't fully apply. Safe/idempotent,
  not a design change.

---

## 4. API surface (`routes/api.php`)

All routes are prefixed `/api` automatically. `routes/web.php` only exposes
a health check (`GET /` → `{"status":"ok"}`).

- **Public:** `POST auth/register`, `POST auth/login`, plus read-only
  dropdown lookups: `GET departments`, `GET programs`, `GET batches`.
- **Any authenticated user** (`auth:sanctum`): `auth/logout`, `auth/user`,
  profile show/update/update-password/destroy, `sessions` index/show,
  `notifications` index/markRead/destroy.
- **`role:student`:** today's classes, weekly schedule, attendance history,
  `POST attendance/mark`, student dashboard.
- **`role:teacher`:** block/unblock a student, teacher dashboard.
- **`role:teacher,hod`:** start/end a session, view a session's attendance
  roster, teacher's own schedule, attendance report.
- **`role:admin,hod`:** browse teachers/students (HOD is scoped to their own
  department).
- **`role:admin`:** full CRUD for departments, programs, program-courses,
  batches, rooms, time slots, teachers, students, timetables, admin
  dashboard.
- **`role:hod`:** HOD dashboard.

Authorization is layered: **route-level `role:` middleware** (coarse) +
**Laravel Policies** (fine-grained, e.g. "this teacher can only block
students in batches they teach").

---

## 5. Core business logic — attendance validation

`app/Services/AttendanceService.php::mark()` is the heart of the system.
Called from `AttendanceController::mark()`, it runs these checks **in
order**, throwing a `BusinessException` (rendered as JSON) on the first
failure and logging a `SuspiciousAttempt` with a `fail_reason`:

1. Session must be `active` (teacher has started it).
2. Student must not be `is_blocked`.
3. The session's timetable batch must match the student's batch.
4. An attendance row must already exist and not already be `present` (no
   double-marking).
5. **GPS geofence** — haversine distance from `config('attendance.campus_*')`
   must be ≤ `geofence_radius_meters` (default 150m). *Silently skipped if
   campus coordinates aren't configured in `.env`.*
6. **RSSI threshold** — submitted signal strength must be ≥ the room's
   `rssi_threshold` (or config default -75 dBm) — rejects "too far away".
7. **Beacon identity** — `detected_uuid`/`detected_major` must exactly
   match the room's configured beacon.

On success: attendance flips to `present`, student gets a notification, and
an audit log entry is written. All of this happens inside `SessionService`
(`start()`/`end()`) and `AttendanceService` (`mark()`), wrapped in DB
transactions where relevant.

**Config that operators must set** (`config/attendance.php`, via `.env`):
```
ATTENDANCE_CAMPUS_LAT=
ATTENDANCE_CAMPUS_LNG=
ATTENDANCE_GEOFENCE_RADIUS=150   (meters, default)
```
If lat/lng are left blank, the geofence check is a silent no-op — this is
an easy way to accidentally ship a build with no location enforcement.

---

## 6. Design history (why BLE + GPS, not WiFi or tokens)

Documented in detail in `project_handoff.md` (root). Two earlier approaches
were tried and abandoned:

1. **WiFi SSID/MAC corroboration** — dropped; too easy to spoof/unreliable
   across device WiFi stacks.
2. **Rotating HMAC token broadcast by the beacon** — dropped because the
   ESP32 beacons have **no RTC and no WiFi**, so a time-based rotating token
   could never stay synced with the server clock.

The current design (**static UUID/Major + GPS geofence + RSSI strength**)
is a deliberate trade-off, with a documented **residual risk**: a captured
static UUID/Major could theoretically be replayed by a modified app that
also fakes GPS and RSSI. This is accepted as good-enough for the current
threat model, not an oversight.

---

## 7. Known gaps / things to be aware of

- **No automated tests for domain logic** — only Laravel's default
  boilerplate example tests exist. `AttendanceService`, `SessionService`,
  policies, and role middleware have no test coverage yet.
- **No factories for domain models** — only the stock `UserFactory` exists,
  which limits writing Feature tests without a lot of manual setup.
  `DatabaseSeeder.php` has a full realistic demo dataset instead
  (departments, programs, batches, students, a demo session with mixed
  attendance) — useful for manual/local testing.
- **No queued jobs, no scheduled tasks** — despite queue infrastructure
  being configured, everything runs synchronously. Sessions must be
  manually ended by a teacher (no auto-expiry job).
- **`project_api_spec.json` / `hod.json` / `teacher.json` (repo root) are
  stale** — they describe the old WiFi-MAC design and should be treated as
  historical reference only, not current ground truth. Prefer
  `project_handoff.md` and the actual code/migrations for current behavior.
- **No file storage / external API integrations** are actually wired up
  (Postmark/SES/Slack/S3 configs are unused Laravel defaults).

---

## 8. Where to look for more detail

- `project_handoff.md` (repo root) — full hardware/firmware architecture,
  decision log, and "what's left to build" notes. The richest existing
  source of project history.
- `database/seeders/DatabaseSeeder.php` — realistic demo data; good place
  to understand expected shapes of every table at a glance.
- `app/Services/` — all real business logic lives here; controllers are
  thin and just validate + delegate.
- `app/Http/Middleware/EnsureUserHasRole.php` + `bootstrap/app.php` — how
  role-based access control and JSON-only error handling are wired up.
