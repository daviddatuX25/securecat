# Phase 1 — Core Operations

> First usable product: registrar/staff encodes applicants, admin approves and schedules, QR is issued, proctor scans on exam day.

---

## 1. Outcome

At the end of Phase 1, the system supports the **minimum end-to-end exam-day workflow**:

1. Admin creates a semester/admission period, courses, rooms, and exam sessions.
2. Staff encodes an applicant's details via a manual-entry form.
3. Admin reviews and approves (or rejects / requests changes) the application.
4. System assigns the approved applicant to a room and session, then generates a **signed QR admission slip**.
5. Proctor scans the QR code on exam day; system validates signature, room, and schedule, then logs the entry.
6. Admin and proctor can view rosters and attendance reports.

All actions are audit-logged (user, action, timestamp, IP).

---

## 2. Scope

### In Scope

| Area | Details |
|------|---------|
| **Auth & RBAC** | Registration and login for Admin, Staff, Proctor. Password hashing (bcrypt/argon2). Session management with configurable timeout. Role-based permission checks on every action. |
| **Semester / Period Management** | Admin creates admission periods with start/end dates. Admin creates courses linked to a period. |
| **Room & Session Setup** | Admin defines rooms (name, capacity). Admin creates exam sessions (date, time, duration, room, course). Proctor assignment to sessions. |
| **Application Encoding** | Staff fills out applicant form (name, contact, course choice, basic personal info). Application enters "pending review" state. |
| **Approval Workflow** | Admin views pending queue. Actions: approve, reject, request changes (with note). Applicant record transitions through states: `draft` -> `pending_review` -> `approved` / `rejected` / `revision_requested`. |
| **Room Assignment** | On approval, admin (or system auto-assign) assigns applicant to an exam session + room respecting capacity. |
| **QR Code Generation** | After assignment, system generates QR containing: `applicant_id`, `exam_session_id`, `room_id`, `schedule_timestamp`, `generated_at`, `HMAC-SHA256 signature`. Admission slip (printable HTML/PDF page with QR + exam details). |
| **Proctor Scanning** | Proctor opens scan page on a mobile device (PWA or responsive page). Camera-based QR scan via HTML5 API. System validates signature, checks room + session + time window. Logs entry with `proctor_id`, `timestamp`, `device_info`. Displays pass/fail with reason. |
| **Anomaly Alerts** | Wrong room, wrong session, outside time window, duplicate scan, invalid/tampered QR — all surfaced to proctor immediately on scan. |
| **Foundational Audit Log** | Every create/update/delete on core entities writes an audit record: `user_id`, `role`, `action`, `entity_type`, `entity_id`, `timestamp`, `ip_address`. Stored in the same database (separate table). Viewable by admin. |
| **Minimal Reporting** | Exam roster per session (list of assigned applicants). Attendance list per session (scanned vs. not-scanned). |

### Non-Goals (Deferred)

- Document upload and verification (Phase 2)
- CSV bulk import / API intake (Phase 2)
- Email or in-app notifications (Phase 2)
- OMR score import, score management (Phase 3)
- Result release and PDF result slips (Phase 3)
- Offline proctor scanning (Phase 4)
- MFA for admins (Phase 4)
- Hash-chained / append-only audit log (Phase 4)
- Examinee self-service portal (future, beyond Phase 4)

---

## 3. User Journeys

### 3.1 Admin — Setup Workflow

```
Admin logs in
  -> Creates a new admission period (e.g., "2nd Semester AY 2026-2027")
  -> Adds courses to the period (e.g., "BSIT", "BSCS")
  -> Defines rooms (e.g., "Room 101, capacity 40")
  -> Creates exam sessions (e.g., "BSIT Exam, 2026-03-15 08:00, Room 101, 2 hrs")
  -> Assigns a proctor to each session
```

### 3.2 Staff — Applicant Encoding

```
Staff logs in
  -> Navigates to "Encode Applicant"
  -> Fills form: full name, email, contact, date of birth, up to three preferred course options
  -> Submits -> application created in "pending_review" state
  -> (Optional) Edits draft if registrar provides corrections before admin review
```

### 3.3 Admin — Application Review & Assignment

```
Admin opens approval queue
  -> Sees list of pending applications with summary info
  -> Clicks an application to review details
  -> Action: Approve / Reject / Request Revision (with note)
  -> On approve: assigns applicant to an exam session + room
      -> System checks room capacity before confirming
      -> System generates signed QR code
      -> Admission slip becomes available for printing
```

### 3.4 Proctor — Exam-Day Scanning

```
Proctor logs in on mobile device
  -> Sees list of assigned exam sessions for today
  -> Selects a session -> views roster (names + photos if available)
  -> Taps "Start Scanning"
  -> Camera activates, scans applicant QR
  -> System validates:
      - Signature authentic?
      - Correct room?
      - Correct session/time window?
      - Already scanned (duplicate)?
  -> Displays green checkmark + applicant name (valid)
     OR red alert with reason (invalid)
  -> Entry logged automatically
  -> Proctor can view live attendance count
```

---

## 4. Data Model (Key Entities)

| Entity | Key Fields | Notes |
|--------|-----------|-------|
| **User** | `id`, `email`, `password_hash`, `role` (admin/staff/proctor), `is_active`, `created_at` | Examinee role exists in enum but is unused in Phase 1. |
| **AdmissionPeriod** | `id`, `name`, `start_date`, `end_date`, `status` (draft/active/closed) | Top-level container. |
| **Course** | `id`, `admission_period_id`, `name`, `code`, `description` | Belongs to a period. |
| **Room** | `id`, `name`, `capacity`, `location_notes` | Reusable across periods. |
| **ExamSession** | `id`, `course_id`, `room_id`, `proctor_id`, `date`, `start_time`, `end_time`, `status` | Links course, room, proctor. |
| **Applicant** | `id`, `first_name`, `last_name`, `email`, `contact_number`, `date_of_birth`, `encoded_by` (staff user_id), `created_at` | Person record; separate from application. |
| **Application** | `id`, `applicant_id`, `course_id` (first preference), `second_course_id`, `third_course_id` (optional), `admission_period_id`, `status` (draft/pending_review/approved/rejected/revision_requested), `admin_notes`, `reviewed_by`, `reviewed_at` | Workflow state machine; applicant has up to three preferred courses. |
| **ExamAssignment** | `id`, `application_id`, `exam_session_id`, `seat_number` (optional), `qr_payload`, `qr_signature`, `assigned_at` | Created on approval + assignment. |
| **ScanEntry** | `id`, `exam_assignment_id`, `proctor_id`, `scanned_at`, `device_info`, `validation_result` (valid/invalid), `failure_reason` | One per scan attempt. |
| **AuditLog** | `id`, `user_id`, `role`, `action`, `entity_type`, `entity_id`, `ip_address`, `timestamp`, `details` (JSON) | Phase 1: no before/after diff; added in Phase 2. |

---

## 5. API / UI Surfaces

### Pages (Server-Rendered or SPA Routes)

| Route Pattern | Role | Purpose |
|---------------|------|---------|
| `/login` | All | Authentication |
| `/admin/dashboard` | Admin | Overview: pending applications, upcoming sessions |
| `/admin/periods/*` | Admin | CRUD admission periods |
| `/admin/courses/*` | Admin | CRUD courses within a period |
| `/admin/rooms/*` | Admin | CRUD rooms |
| `/admin/sessions/*` | Admin | CRUD exam sessions, assign proctors |
| `/admin/applications` | Admin | Approval queue (list + detail + actions) |
| `/admin/assignments` | Admin | View/manage exam assignments |
| `/admin/audit-log` | Admin | Browse audit records with filters |
| `/admin/reports/roster` | Admin | Exam roster per session |
| `/admin/reports/attendance` | Admin | Attendance list per session |
| `/staff/encode` | Staff | Applicant encoding form |
| `/staff/applications` | Staff | View applications encoded by this staff member |
| `/proctor/sessions` | Proctor | List of assigned sessions |
| `/proctor/scan/:session_id` | Proctor | QR scanning interface |
| `/proctor/attendance/:session_id` | Proctor | Live attendance view |
| `/print/admission-slip/:assignment_id` | Admin/Staff | Printable QR admission slip |

### API Endpoints (If SPA Architecture)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/auth/login` | Authenticate, return session/token |
| POST | `/api/auth/logout` | Destroy session |
| CRUD | `/api/admission-periods` | Manage periods |
| CRUD | `/api/courses` | Manage courses |
| CRUD | `/api/rooms` | Manage rooms |
| CRUD | `/api/exam-sessions` | Manage sessions |
| POST | `/api/applicants` | Create applicant record |
| CRUD | `/api/applications` | Create, list, update status |
| POST | `/api/applications/:id/approve` | Approve + trigger assignment |
| POST | `/api/applications/:id/reject` | Reject with notes |
| POST | `/api/applications/:id/request-revision` | Request changes |
| POST | `/api/exam-assignments` | Assign applicant to session |
| GET  | `/api/exam-assignments/:id/qr` | Get QR payload for printing |
| POST | `/api/scan` | Submit QR scan for validation |
| GET  | `/api/reports/roster/:session_id` | Exam roster |
| GET  | `/api/reports/attendance/:session_id` | Attendance list |
| GET  | `/api/audit-log` | Paginated audit records |

---

## 6. Security Controls (Phase 1)

| IAS Principle | Control | Implementation |
|---------------|---------|----------------|
| **Confidentiality** | RBAC on every route/endpoint | Middleware checks `user.role` against allowed roles before processing. |
| **Confidentiality** | HTTPS / TLS | All traffic encrypted; HTTP redirects to HTTPS. |
| **Confidentiality** | Encrypted DB connections | PostgreSQL SSL mode enforced. |
| **Integrity** | Input validation | Server-side validation on all form fields (type, length, format). |
| **Integrity** | CSRF protection | Token-based CSRF on all state-changing requests. |
| **Integrity** | Signed QR codes | HMAC-SHA256 signature prevents forgery; verified on every scan. |
| **Availability** | Session timeout | Configurable inactivity timeout (default 15 min for admin). |
| **Accountability** | Audit logging | Every mutating action logged with user, role, action, timestamp, IP. |
| **Non-Repudiation** | QR entry log | Scan entries are immutable records linking applicant, proctor, time, device. |

---

## 7. Acceptance Criteria

### Demo Script

1. **Admin setup:** Create period -> course -> room -> exam session -> assign proctor. Verify all appear in dashboards.
2. **Staff encodes:** Log in as staff, encode 3 applicants for the course. Verify they appear in admin's approval queue.
3. **Admin approves:** Approve 2 applicants, reject 1 with a note. Verify state transitions. Assign approved applicants to the session. Verify room capacity is respected.
4. **Print slip:** Open admission slip for an approved applicant. Verify QR code is visible and contains correct data.
5. **Proctor scans (valid):** Log in as proctor on mobile. Scan a valid QR code. Verify green result with applicant name.
6. **Proctor scans (invalid — wrong room):** Scan a QR for a different session. Verify red alert with "wrong session" message.
7. **Proctor scans (duplicate):** Scan the same QR again. Verify "already scanned" warning.
8. **Reports:** View roster — shows all assigned applicants. View attendance — shows scanned vs. not-scanned.
9. **Audit log:** Admin opens audit log. Verify entries for all actions above (create period, encode applicant, approve, scan, etc.).
10. **RBAC check:** Staff tries to access `/admin/audit-log` — verify 403 / redirect. Proctor tries to access `/staff/encode` — verify 403 / redirect.

### Minimum Test Checklist

- [ ] Authentication: login, logout, session expiry.
- [ ] RBAC: each role can only access permitted routes.
- [ ] Application state machine: all transitions (draft -> pending -> approved/rejected/revision).
- [ ] Room capacity: assignment fails gracefully when room is full.
- [ ] QR signature: valid QR passes; tampered QR fails.
- [ ] Scan anomalies: wrong room, wrong time, duplicate — all detected.
- [ ] Audit log: at least one record per mutating action.
- [ ] Input validation: required fields, email format, date format.
- [ ] CSRF: state-changing requests without valid token are rejected.

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Staff enters incorrect applicant data (no self-service correction) | Wrong QR issued, wrong person shows up | Admin review step catches errors; revision-request workflow allows corrections before QR generation. |
| QR secret key compromised | Forged QR codes admitted | Store key in env variable, rotate periodically, monitor for anomalous scan patterns. |
| Single server failure during exam day | Proctors cannot validate | Phase 1 accepts this risk; Phase 4 adds offline scanning. Short-term: print backup roster as fallback. |
| Proctor device camera quality | QR scan failures | Recommend minimum 8 MP rear camera; provide manual lookup fallback (search by name). |
| Scope creep from stakeholders wanting scoring in Phase 1 | Delays delivery | Phase boundary is firm; scoring ships in Phase 3. Communicate early. |
