# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Documentation Rule

**Any time you add, modify, or remove API endpoints, you MUST update the corresponding documentation files:**

- Admin endpoints → `documentation/admin endpoints documentation/`
- Teacher endpoints → `documentation/teacher endpoints documentation/`
- Student endpoints → `documentation/student endpoints documentation/`

For each affected role:
1. Create or update the numbered detail file (e.g. `27-group-announcements.md`) with the full endpoint spec (object shape, request body, response examples).
2. Add the new section to the Table of Contents in `00-index.md`.
3. Add the endpoint row(s) to the Quick Reference table in `00-index.md`.

## Common Commands

```bash
# Development
php artisan serve                    # Start dev server (http://localhost:8000)
php artisan migrate                  # Run pending migrations
php artisan migrate:fresh --seed     # Reset database and seed
php artisan route:list               # List all routes

# OAuth / Auth
php artisan passport:install         # Install Passport OAuth2 keys
php artisan create-admin-user        # Create an admin user via CLI
php artisan check-passport-keys      # Verify Passport key setup

# Cache / Storage
php artisan cache:clear
php artisan config:clear
php artisan create-storage-directories  # Initialize GCS/local storage dirs

# Tests (custom scripts, not PHPUnit)
php tests/test-db.php
php tests/test-users.php
```

## Architecture Overview

### Multi-Role API

This is a **Laravel 10 REST API** with three distinct user roles: **Admin**, **Teacher**, and **Student**. All routes are under `/api` with sub-prefixes `/api/admin`, `/api/teacher`, `/api/student`.

Authentication uses **Laravel Passport** (OAuth2). Role enforcement is via middleware:
- `app/Http/Middleware/Role/AdminMiddleware.php`
- `app/Http/Middleware/Role/TeacherMiddleware.php`
- `app/Http/Middleware/Role/StudentMiddleware.php`

Routes are registered in `app/Providers/RouteServiceProvider.php` and split across many files under `routes/admin/`, `routes/teacher/`, `routes/student/`.

### Response Convention

Controllers return `response()->json()` directly. The `ApiResponder` trait (`app/Http/Traits/ApiResponder.php`) exists but is rarely used — do not add it to controllers unless it is already present.

### Services Layer

Business logic lives in `app/Services/`:
- **ZoomService** — Server-to-Server OAuth for creating Zoom meetings; credentials stored per `MeetingAccount` model. Meeting `start_time` is always sent as UTC with `timezone: UTC` in the Zoom API payload.
- **EuPlatescService** — Romanian payment gateway; handles EUR→RON conversion and payment form generation
- **SmartBillService** — Romanian invoicing/accounting sync
- **NotificationService** — Sends notifications via Pusher to users
- **AchievementService** — Student streaks and gamification
- **GcsService** — Google Cloud Storage file management

### Timezone Convention

All `event_date` and `event_time` values on the `Event` model are **stored in UTC** in the database.

**Write path (admin/teacher creating or updating events):**
Input `event_date` + `event_time` are interpreted as the acting user's local timezone and converted to UTC before persisting. Both fields must be submitted together for the conversion to apply.

**Read path (any role retrieving events):**
UTC values are converted to the requesting user's `effective_timezone` before being returned in API responses. This applies in all event endpoints, the student schedule, and the student dashboard.

**User timezone setting:**
- All users (admin, teacher, student) have an optional `timezone` VARCHAR(50) field on the `users` table.
- `NULL` defaults to `Europe/Bucharest` via the `getEffectiveTimezoneAttribute()` accessor on the `User` model.
- Users set their timezone via `PUT /api/{role}/profile` with a valid IANA timezone string (e.g. `Europe/Bucharest`, `America/New_York`).

**Conversion utility:**
`app/Helpers/TimezoneHelper.php` is the sole conversion point:
- `TimezoneHelper::toUtc(string $date, string $time, string $timezone): Carbon`
- `TimezoneHelper::fromUtc(Carbon $utc, string $timezone): array` — returns `['date' => 'Y-m-d', 'time' => 'H:i']`

**Zoom meetings:**
`ZoomService` sends `start_time` in UTC format and explicitly passes `'timezone' => 'UTC'` to the Zoom API.

**Group schedule_days:**
`schedule_days[].time` values on the `Group` model are also stored as UTC and converted on read/write using `TimezoneHelper::scheduleTimeToUtc()` / `scheduleTimeFromUtc()`. A fixed anchor date (`2026-01-05`) is used for conversion to produce a stable UTC offset unaffected by DST transitions.

**Attendance session times:**
`attendance.session_date` and `attendance.session_time` are stored as UTC (converted from actor timezone on `takeAttendance`), and converted back to the requesting user's timezone when returned by `getAttendance`.

### Payment & Product System

Students purchase **Products** (tracked via `ProductAcquisition`) using **PaymentProfiles**:
- **Product** — Sellable item; subtypes `SingleProduct` (test + optional teacher assistance) and `CourseProduct` (course bundle)
- **PaymentProfile** — Student billing details; type is `physical_person` or `company`, with a linked `PaymentProfilePhysicalPerson` or `PaymentProfileCompany`
- **ProductAcquisition** — Purchase record linking a student, product, and payment profile; lifecycle: `pending_payment` → `paid` → `active` → `completed` (or `cancelled`/`expired`). Stores `groups_access` and `tests_access` JSON arrays set by admin after payment
- **Invoice** / **InvoicePayment** — SmartBill invoice tracking and EuPlatesc payment responses

Admin routes: `routes/admin/products.php` | Student routes: `routes/student/products.php`

### Assessment System

Complex polymorphic structure:
- **Exams** — Top-level student assessments with status history (`ExamStatusHistory`)
- **Tests** — Teacher-created; composed of `TestSection` → `TestQuestion` (polymorphic to 15+ question type models)
- **Homework** — Similar structure: `HomeworkSection` → questions; students submit via `HomeworkSubmission`

Question type models include: `MultipleChoiceQuestion`, `GapFillQuestion`, `RephraseQuestion`, `WordFormationQuestion`, `ReplaceQuestion`, `CorrectQuestion`, `WordDerivationQuestion`, `TextCompletionQuestion`, `CorrelationQuestion`, `ReadingQuestion`, `WritingQuestion`, `SpeakingQuestion`, and test-specific variants.

### Real-Time Features

**Pusher** broadcasts are configured in `config/broadcasting.php`. The `MessageSent` event (`app/Events/MessageSent.php`) drives chat. `BroadcastServiceProvider` registers channel authorization in `routes/channels.php`.

### AI Integration

Anthropic Claude SDK (`anthropic/anthropic-sdk-php`) is used in `AiAssistantController`, `NormalAssistant`, `MedicalAssistant`, and `TranslationController`. Model and version are configured via `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`, `ANTHROPIC_VERSION` env vars.

### Key Configuration Files

| File | Purpose |
|---|---|
| `config/payment.php` | EuPlatesc credentials and EUR/RON rate |
| `config/smartbill.php` | SmartBill API credentials |
| `config/chat.php` | Default admin ID for student chats |
| `config/passport.php` | OAuth2 token lifetimes |
| `config/filesystems.php` | GCS disk configuration |

### Database

MySQL with 91 migrations. Notable patterns:
- Soft deletes on groups
- JSON columns for array data (guests, group assignments)
- Pivot tables: `group_student`, `student_exam`
- `DatabaseLog` model for system-wide audit logging
