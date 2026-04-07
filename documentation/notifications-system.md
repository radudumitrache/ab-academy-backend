# Notifications System

This document describes **when** each notification type is triggered and **which users** receive it.

---

## Notification Types

| Type | Source | Description |
|---|---|---|
| `Exam` | Admin | Exam lifecycle events |
| `Homework` | Admin / Student / Teacher | Homework lifecycle + submission + grading |
| `Schedule` | Admin | Event/calendar lifecycle events |
| `Message` | Admin | New chat messages |
| `Payment` | Admin | Invoice issuance |
| `Announcement` | Admin / Teacher | Group announcements |

---

## By Trigger

### Exam Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Exam **created** | All enrolled students + teachers whose groups contain enrolled students | `"A new exam '{name}' has been scheduled for {date}."` |
| Exam **updated** | Same as above | `"The exam '{name}' has been updated. Date: {date}, Status: {status}."` |
| Exam **deleted** | Same as above | `"The exam '{name}' has been cancelled."` |

- Source: `Admin`
- Email: **yes**

---

### Homework Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Homework **created** | Users in `people_assigned` + all students from groups in `groups_assigned` | `"New homework '{title}' has been assigned, due on {due_date}."` |
| Homework **updated** | Same as above | `"Homework '{title}' has been updated."` |
| Homework **deleted** | Same as above | `"Homework '{title}' has been removed."` |
| Student **submits** homework | The homework's assigned teacher | `"Student {username} submitted homework '{title}'."` |
| Teacher **grades** a submission | The student who submitted | `"Your homework '{title}' has been graded."` |

- Source: `Admin` (create/update/delete), `Student` (submission), `Teacher` (grading)
- Email: **yes** for create/update/delete; **no** for submission and grading

---

### Schedule (Event) Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Event **created** | Event guests + event organizer (if set) | `"A new event '{title}' has been scheduled for {date}."` |
| Event **updated** | Same as above | `"The event '{title}' has been updated. Date: {date}."` |
| Event **deleted** | Same as above | `"The event '{title}' has been cancelled."` |
| Student **exhausts all course sessions** | All admins + the affected student | Admin: `"Student {name} (ID: {id}) has used all course sessions for {product}. The student has been removed from the related group(s)."` / Student: `"You have used all your course sessions for your current plan. Please contact us to renew your subscription."` |

- Source: `Admin`
- Email: **yes** for event lifecycle; **yes for admins only** on session exhaustion

---

### Message Notifications

| Trigger | Recipients | Message |
|---|---|---|
| **Admin** sends a chat message | The student in the conversation | `"You have a new message from {admin_username}."` |

- Source: `Admin`
- Email: **no**
- Note: Student-to-admin messages do **not** trigger a notification.

---

### Payment Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Invoice **created** | The student the invoice belongs to | `"A new invoice '{title}' for {value} {currency} has been issued, due on {due_date}."` |

- Source: `Admin`
- Email: **yes**

---

### Announcement Notifications

| Trigger | Recipients | Message |
|---|---|---|
| Group announcement **created** (by admin or teacher) | All students in the group | `"New announcement in your group: {title}"` |

- Source: `Admin` or `Teacher` depending on who created it
- Email: **no**

---

## By User Role

### Admin

Admins receive:
- All **Exam** notifications for exams they manage
- **Schedule** notification when a student exhausts all course sessions (email included)
- All other notifications they are explicitly listed as a guest/organizer on

Admins can also **manually create** notifications for any user via `POST /api/admin/notifications`.

---

### Teacher

Teachers receive:
- **Exam** notifications when at least one student from their groups is enrolled in the exam
- **Homework** submission notifications when a student submits homework assigned by that teacher
- **Exam / Event** notifications if they are listed as a guest or organizer

---

### Student

Students receive:
- **Exam** notifications when they are enrolled in an exam (created, updated, or deleted)
- **Homework** notifications when homework is assigned to them directly or via their group (created, updated, or deleted)
- **Homework grading** notification when a teacher grades their submission
- **Schedule** notification when an event they are a guest of changes, or when they exhaust all course sessions
- **Message** notification when an admin sends them a chat message
- **Payment** notification when an invoice is issued for them
- **Announcement** notification when a new announcement is posted in their group

---

## API Endpoints

All three roles share the same endpoint structure under their respective prefixes (`/api/admin`, `/api/teacher`, `/api/student`):

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/notifications` | List own notifications (supports filters below) |
| `PUT` | `/notifications/seen-all` | Mark all notifications as seen |
| `PUT` | `/notifications/{id}/seen` | Mark a single notification as seen |
| `DELETE` | `/notifications/{id}` | Delete a notification |
| `POST` | `/notifications` | **Admin only** — manually create a notification |
| `POST` | `/notifications/test-email` | **Admin only** — send a test email notification |

### Query Filters for `GET /notifications`

| Parameter | Values | Description |
|---|---|---|
| `is_seen` | `true` / `false` | Filter by read status |
| `notification_type` | `Exam`, `Schedule`, `Homework`, `Message`, `Payment`, `Announcement` | Filter by type |
| `notification_source` | `Admin`, `Student`, `Teacher` | Filter by who triggered it |
| `notification_time` | Date string | Filter by date |

Results are ordered by `notification_time` descending.

---

## Email Delivery

Email notifications are queued (not sent synchronously) and are controlled by the `EMAIL_NOTIFICATIONS` environment variable. Setting it to `false` disables all email notifications. The email subject format is:

```
{AppName} — {NotificationType} Notification
```

Template: `resources/views/emails/notification.blade.php`

---

## Implementation Notes

Notifications are triggered automatically by six **Eloquent observers** registered in `AppServiceProvider`:

| Observer | Watched Model |
|---|---|
| `ExamObserver` | `Exam` |
| `HomeworkObserver` | `Homework` |
| `EventObserver` | `Event` |
| `MessageObserver` | `Message` |
| `InvoiceObserver` | `Invoice` |
| `AttendanceObserver` | `Attendance` |

The `NotificationService` (`app/Services/NotificationService.php`) exposes two methods:
- `notify($userIds, $message, $source, $type)` — writes to the `notifications` table
- `notifyByEmail($userIds, $message, $type)` — queues email delivery

Notifications are stored individually per recipient (one row per user per event).
