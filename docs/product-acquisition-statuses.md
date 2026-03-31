# Product Acquisition Statuses

## `pending_payment`

**Entry point.** Created when a student initiates a purchase (via `purchase` or `renew`) or when an admin manually creates an acquisition. The EuPlatesc checkout form has been generated but payment has not been confirmed yet. If the EuPlatesc IPN callback comes back with a failed/non-zero action, the status stays (or reverts back to) `pending_payment`.

---

## `paid`

**Payment confirmed by EuPlatesc.** Set automatically by the `EuPlatescController` IPN/return callback when `action === 0` (approved). At this point `paid_at` and `acquisition_date` are recorded. The student has paid but **has not yet received access** — they are waiting for admin to assign groups/tests.

Can also be set manually by admin via `updateStatus` for cash/bank-transfer scenarios.

Invoices can be created in SmartBill for acquisitions in this status.

---

## `active`

**Access granted.** Set by admin via `grantAccess`. Requires the acquisition to currently be in `paid` status. At this point `groups_access` and `tests_access` are populated, the student is enrolled, and `remaining_courses` is initialized (for course products). This is the normal "in use" state.

Invoices can also be created/modified in SmartBill for acquisitions in this status.

---

## `completed`

**Finished naturally.** Set manually by admin (via `updateStatus`) when the student has consumed their purchase — e.g. all course sessions used up, or the exam period ended. `completion_date` and `is_completed` are set alongside. This status (along with `expired`) **allows the student to renew** the acquisition.

---

## `cancelled`

**Terminated before completion.** Set manually by admin. Represents a purchase that was stopped — e.g. the student withdrew, there was a dispute, or the order was rejected. Acquisitions in this status **can be permanently deleted** by admin.

---

## `expired`

**Time-limited access ran out.** Set manually by admin. Similar to `cancelled` in that it is a terminal state, but specifically conveys that the access period elapsed. Like `completed`, it **allows the student to renew**. Also **deletable** by admin.

---

## Status Transition Summary

```
pending_payment ──(EuPlatesc IPN approved)──► paid
pending_payment ──(admin manual)──────────────► any

paid ──(admin grantAccess)──────────────────► active
paid ──(admin updateStatus)─────────────────► any

active ──(admin updateStatus)───────────────► completed / cancelled / expired

completed / expired ──(student renew)───────► new pending_payment (new record)

pending_payment / cancelled / expired ──────► deletable
```
