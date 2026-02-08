# Phase 1 Tasks — Seed

## T1.1.1 — DB migration: users table
Story: S1.1. users: id, email, password_hash, role, first_name, last_name, is_active, created_at, updated_at

## T1.1.2 — POST /api/auth/login endpoint
Story: S1.1. Validate credentials, hash check, create session, return session/token; rate-limit attempts

## T1.1.3 — Session create/store in Redis with TTL
Story: S1.1. Redis key e.g. session:{id}, TTL from config (e.g. 15 min inactivity)

## T1.2.1 — POST /api/auth/logout endpoint
Story: S1.2. Invalidate session in Redis

## T1.3.1 — RBAC middleware: role-per-route mapping
Story: S1.3. Map route/action to allowed roles (e.g. /api/audit-log → admin only)

## T1.3.2 — Apply RBAC to all API routes and UI route guards
Story: S1.3. 403 for unauthorized; UI redirect or hide menu for disallowed routes

## T2.1.1 — DB migration: admission_periods table
Story: S2.1. Migrations per PHASE-1-scope-freeze

## T2.1.2 — CRUD API: admission-periods + audit events
Story: S2.1. Validate input (dates); emit audit on create/update/delete

## T2.1.3 — UI: list/create/edit admission periods (Admin)
Story: S2.1. Forms with validation; list with edit/delete

## T2.2.1 — DB migration: courses table
Story: S2.2. FK to admission_period

## T2.2.2 — CRUD API: courses + audit events
Story: S2.2. Validate input; emit audit on create/update/delete

## T2.2.3 — UI: list/create/edit courses per period (Admin)
Story: S2.2. Forms with validation; list with edit/delete

## T2.3.1 — DB migration: rooms table
Story: S2.3. name, capacity, location notes

## T2.3.2 — CRUD API: rooms + audit events
Story: S2.3. Validate input (capacity, FKs); emit audit

## T2.3.3 — UI: list/create/edit rooms (Admin)
Story: S2.3. Forms with validation; list with edit/delete

## T2.4.1 — DB migration: exam_sessions table
Story: S2.4. course, room, date, time, duration, proctor_id

## T2.4.2 — CRUD API: exam-sessions (include proctor_id) + audit events
Story: S2.4. Validate input; emit audit on create/update/delete

## T2.4.3 — UI: list/create/edit exam sessions, assign proctor (Admin)
Story: S2.4. Forms with validation; list with edit/delete

## T3.1.1 — DB migration: applicants, applications tables
Story: S3.1. applicants, applications; application status enum

## T3.1.2 — POST applicants + application + audit
Story: S3.1. first_course_id required; second/third optional, distinct; status=pending_review

## T3.1.3 — UI: encode applicant form (Staff)
Story: S3.1. Three preferred course dropdowns, validation messages

## T3.2.1 — POST approve/reject/request-revision endpoints + audit
Story: S3.2. approve, reject, revision_request with admin_notes

## T3.2.2 — UI: approval queue list and detail, action buttons (Admin)
Story: S3.2. List pending_review; detail view; Approve/Reject/Request Revision

## T3.3.1 — DB migration: exam_assignments table
Story: S3.3. application_id, exam_session_id, seat_number, qr_payload, qr_signature, assigned_at

## T3.3.2 — Assign-to-session logic: capacity check, create ExamAssignment
Story: S3.3. Count assignments for session room; fail if >= room.capacity

## T3.3.3 — QR generation (HMAC-SHA256) on assignment + store payload/signature
Story: S3.3. Payload JSON; HMAC-SHA256 hex signature

## T3.3.4 — UI: assign approved applicant to session (Admin), capacity feedback
Story: S3.3. Assign to session flow: select session, confirm; show capacity

## T4.1.1 — GET admission slip (HTML/PDF) with QR + verification
Story: S4.1. QR image, exam details; verify signature server-side

## T4.1.2 — UI route: print admission slip (Admin/Staff)
Story: S4.1. Route e.g. /print/admission-slip/:assignment_id

## T4.2.1 — DB migration: scan_entries table
Story: S4.2. exam_assignment_id, proctor_id, scanned_at, device_info, validation_result, failure_reason

## T4.2.2 — POST /api/scan: validate payload, check session/room/time, create ScanEntry
Story: S4.2. Decode payload, verify HMAC; check proctor/session/date-time; insert ScanEntry

## T4.2.3 — UI: proctor session list, scan page (camera/manual), pass/fail display
Story: S4.2. Proctor sees assigned sessions; camera or manual QR; valid/invalid + reason

## T4.3.1 — Duplicate scan detection + wrong room/session/time in scan endpoint
Story: S4.3. Duplicate ScanEntry; session/room mismatch → wrong session/room

## T4.3.2 — Audit event for each scan (valid/invalid + reason)
Story: S4.3. Audit event scan.validate with result and reason

## T5.1.1 — GET /api/reports/roster/:session_id
Story: S5.1. Assignments for session with applicant/app details

## T5.1.2 — UI: roster view per session (Admin/Proctor)
Story: S5.1. Read-only; Proctor scoped to assigned sessions

## T5.2.1 — GET /api/reports/attendance/:session_id
Story: S5.2. Join ScanEntry, show scanned vs not

## T5.2.2 — UI: attendance view per session (Admin/Proctor)
Story: S5.2. Read-only; Proctor scoped to assigned sessions

## T5.3.1 — Dashboard API: pending count, upcoming sessions
Story: S5.3. Count pending_review; exam_sessions where date >= today

## T5.3.2 — UI: admin dashboard (counts, links)
Story: S5.3. Counts and links for approval queue and upcoming sessions

## T6.1.1 — DB migration: audit_log table
Story: S6.1. user_id, role, action, entity_type, entity_id, ip_address, timestamp, details (JSON)

## T6.1.2 — Audit logger: log(user, role, action, entity_type, entity_id, ip, timestamp, details)
Story: S6.1. Single interface called by all mutating flows

## T6.1.3 — Emit audit event from every mutating endpoint (list in API spec)
Story: S6.1. See 08-api-spec-phase1.md for event list per endpoint

## T6.2.1 — GET /api/audit-log (paginated, filters)
Story: S6.2. Filters: user_id, action, entity_type, date_from, date_to

## T6.2.2 — UI: audit log browse + filters (Admin only)
Story: S6.2. Table view; filter form; role guard admin-only
