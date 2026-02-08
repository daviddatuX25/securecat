# Phase 1 — Tasks

> Implementation-ready tasks for Phase 1. Each task ties to stories in [PHASE-1-STORIES.md](PHASE-1-STORIES.md). **Prerequisite:** [PHASE-0-TASKS.md](PHASE-0-TASKS.md) (Laravel + TALL bootstrap) must be complete.

**Phase:** 1

---

## Task Index by Story

| Task ID | Story | Summary |
|---------|--------|---------|
| T1.1.1 | S1.1 | DB migration: users table |
| T1.1.2 | S1.1 | POST /api/auth/login endpoint |
| T1.1.3 | S1.1 | Session create/store in Redis with TTL |
| T1.2.1 | S1.2 | POST /api/auth/logout endpoint |
| T1.3.1 | S1.3 | RBAC middleware: role-per-route mapping |
| T1.3.2 | S1.3 | Apply RBAC to all API routes and UI route guards |
| T2.1.1 | S2.1 | DB migration: admission_periods table |
| T2.1.2 | S2.1 | CRUD API: admission-periods + audit events |
| T2.1.3 | S2.1 | UI: list/create/edit admission periods (Admin) |
| T2.2.1 | S2.2 | DB migration: courses table |
| T2.2.2 | S2.2 | CRUD API: courses + audit events |
| T2.2.3 | S2.2 | UI: list/create/edit courses per period (Admin) |
| T2.3.1 | S2.3 | DB migration: rooms table |
| T2.3.2 | S2.3 | CRUD API: rooms + audit events |
| T2.3.3 | S2.3 | UI: list/create/edit rooms (Admin) |
| T2.4.1 | S2.4 | DB migration: exam_sessions table |
| T2.4.2 | S2.4 | CRUD API: exam-sessions (include proctor_id) + audit events |
| T2.4.3 | S2.4 | UI: list/create/edit exam sessions, assign proctor (Admin) |
| T3.1.1 | S3.1 | DB migration: applicants, applications tables |
| T3.1.2 | S3.1 | POST applicants + application (first_course_id required; second_course_id, third_course_id optional, distinct; status=pending_review) + audit |
| T3.1.3 | S3.1 | UI: encode applicant form (Staff): three preferred course dropdowns, validation messages |
| T3.2.1 | S3.2 | POST approve/reject/request-revision endpoints + audit |
| T3.2.2 | S3.2 | UI: approval queue list and detail, action buttons (Admin) |
| T3.3.1 | S3.3 | DB migration: exam_assignments table |
| T3.3.2 | S3.3 | Assign-to-session logic: capacity check, create ExamAssignment |
| T3.3.3 | S3.3 | QR generation (HMAC-SHA256) on assignment + store payload/signature |
| T3.3.4 | S3.3 | UI: assign approved applicant to session (Admin), capacity feedback |
| T4.1.1 | S4.1 | GET admission slip (HTML/PDF) with QR + verification |
| T4.1.2 | S4.1 | UI route: print admission slip (Admin/Staff) |
| T4.2.1 | S4.2 | DB migration: scan_entries table |
| T4.2.2 | S4.2 | POST /api/scan: validate payload, check session/room/time, create ScanEntry |
| T4.2.3 | S4.2 | UI: proctor session list, scan page (camera/manual), pass/fail display |
| T4.3.1 | S4.3 | Duplicate scan detection + wrong room/session/time in scan endpoint |
| T4.3.2 | S4.3 | Audit event for each scan (valid/invalid + reason) |
| T5.1.1 | S5.1 | GET /api/reports/roster/:session_id |
| T5.1.2 | S5.1 | UI: roster view per session (Admin/Proctor) |
| T5.2.1 | S5.2 | GET /api/reports/attendance/:session_id |
| T5.2.2 | S5.2 | UI: attendance view per session (Admin/Proctor) |
| T5.3.1 | S5.3 | Dashboard API: pending count, upcoming sessions |
| T5.3.2 | S5.3 | UI: admin dashboard (counts, links) |
| T6.1.1 | S6.1 | DB migration: audit_log table |
| T6.1.2 | S6.1 | Audit logger: log(user, role, action, entity_type, entity_id, ip, timestamp, details) |
| T6.1.3 | S6.1 | Emit audit event from every mutating endpoint (list in API spec) |
| T6.2.1 | S6.2 | GET /api/audit-log (paginated, filters) |
| T6.2.2 | S6.2 | UI: audit log browse + filters (Admin only) |

---

## Task Details (Implementation Hints)

### Auth & RBAC (E1)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T1.1.1 | 1 | — | users: id, email, password_hash, role, first_name, last_name, is_active, created_at, updated_at |
| T1.1.2 | 1 | All | Validate credentials, hash check (bcrypt/argon2), create session, return session/token; rate-limit attempts |
| T1.1.3 | 1 | — | Redis key e.g. session:{id}, TTL from config (e.g. 15 min inactivity) |
| T1.2.1 | 1 | All | Invalidate session in Redis |
| T1.3.1 | 1 | — | Map route/action to allowed roles (e.g. /api/audit-log → admin only) |
| T1.3.2 | 1 | All | 403 for unauthorized; UI redirect or hide menu for disallowed routes |

### Scheduling (E2)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T2.1.1–T2.4.1 | 1 | Admin | Migrations per [PHASE-1-scope-freeze](../phases/PHASE-1-scope-freeze.md) §3 |
| T2.x.2 | 1 | Admin | Validate input (dates, capacity, FKs); emit audit on create/update/delete |
| T2.x.3 | 1 | Admin | Forms with validation; list with edit/delete where applicable |

### Application Lifecycle (E3)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T3.1.1 | 1 | — | applicants, applications; application status enum |
| T3.1.2 | 1 | Registrar | Validation per 07-interfaces §2 (name, email, DoB, first_course_code required; second/third optional, distinct); audit application.create |
| T3.1.3 | 1 | Registrar | Single form; three preferred course dropdowns from active period courses |
| T3.2.1 | 1 | Admin | approve → optional assign; reject/revision with admin_notes; audit application.approve/reject/revision_request |
| T3.2.2 | 1 | Admin | List pending_review; detail view; buttons Approve / Reject / Request Revision |
| T3.3.1 | 1 | — | exam_assignments: application_id, exam_session_id, seat_number, qr_payload, qr_signature, assigned_at |
| T3.3.2 | 1 | Admin | Count assignments for session room; fail if >= room.capacity |
| T3.3.3 | 1 | — | Payload JSON: applicant_id, exam_session_id, room_id, schedule, generated_at; HMAC-SHA256 hex signature |
| T3.3.4 | 1 | Admin | After approve, "Assign to session" flow: select session, confirm; show capacity |

### QR & Scanning (E4)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T4.1.1 | 1 | Admin, Registrar | Page with QR image (from qr_payload + optional render), exam details; verify signature server-side for display |
| T4.1.2 | 1 | Admin, Registrar | Route e.g. /print/admission-slip/:assignment_id, role guard |
| T4.2.1 | 1 | — | scan_entries: exam_assignment_id, proctor_id, scanned_at, device_info, validation_result, failure_reason |
| T4.2.2 | 1 | Proctor | Decode payload, verify HMAC; check proctor assigned to session; check session date/time window; insert ScanEntry |
| T4.2.3 | 1 | Proctor | Proctor sees only assigned sessions; scan UI: camera or manual QR input; show valid/invalid + reason |
| T4.3.1 | 1 | Proctor | Same assignment already has valid ScanEntry → duplicate; session/room mismatch → wrong session/room |
| T4.3.2 | 1 | — | Audit event scan.validate with result and reason |

### Reports (E5)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T5.1.1, T5.2.1 | 1 | Admin, Proctor | Roster: assignments for session with applicant/app details; Attendance: join ScanEntry, show scanned vs not |
| T5.1.2, T5.2.2 | 1 | Admin, Proctor | Read-only pages; Proctor scoped to assigned sessions only |
| T5.3.1, T5.3.2 | 1 | Admin | Count applications where status=pending_review; list exam_sessions where date >= today, ordered |

### Audit (E6)

| Task | Phase | Roles | Notes |
|------|-------|-------|--------|
| T6.1.1 | 1 | — | audit_log: id, user_id, role, action, entity_type, entity_id, ip_address, timestamp, details (JSON) |
| T6.1.2 | 1 | — | Single interface called by all mutating flows |
| T6.1.3 | 1 | — | See [08-api-spec-phase1.md](../../architecture/08-api-spec-phase1.md) for event list per endpoint |
| T6.2.1 | 1 | Admin | Pagination (limit/offset or cursor); optional filters: user_id, action, entity_type, date_from, date_to |
| T6.2.2 | 1 | Admin | Table view; filter form; role guard admin-only |

---

## Role–Task Summary

| Role | Tasks (key areas) |
|------|-------------------|
| **Admin** | T1.*, T2.*, T3.2.*, T3.3.*, T4.1.*, T5.*, T6.2.* (auth, setup, approval, assignment, slip, reports, audit) |
| **Registrar (Staff)** | T1.*, T3.1.*, T4.1.* (auth, encode, print slip) |
| **Proctor** | T1.*, T4.2.*, T4.3.*, T5.1.*, T5.2.* (auth, scan, roster/attendance for assigned sessions) |
