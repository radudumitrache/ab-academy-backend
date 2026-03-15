# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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

All controllers use the `ApiResponder` trait (`app/Http/Traits/ApiResponder.php`) for consistent JSON responses.

### Services Layer

Business logic lives in `app/Services/`:
- **ZoomService** — Server-to-Server OAuth for creating Zoom meetings; credentials stored per `MeetingAccount` model
- **EuPlatescService** — Romanian payment gateway; handles EUR→RON conversion and payment form generation
- **SmartBillService** — Romanian invoicing/accounting sync
- **NotificationService** — Sends notifications via Pusher to users
- **AchievementService** — Student streaks and gamification
- **GcsService** — Google Cloud Storage file management

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

MySQL with 87 migrations. Notable patterns:
- Soft deletes on groups
- JSON columns for array data (guests, group assignments)
- Pivot tables: `group_student`, `student_exam`
- `DatabaseLog` model for system-wide audit logging
