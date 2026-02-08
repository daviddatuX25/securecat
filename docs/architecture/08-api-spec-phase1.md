# 08 — API Spec (Phase 1)

> REST/JSON contract for Phase 1. Auth, validation, and audit aligned to [06-dfd-trust-boundaries.md](06-dfd-trust-boundaries.md).

**Scope:** Phase 1 only. References: [PHASE-1-scope-freeze.md](../plans/phases/PHASE-1-scope-freeze.md), [07-interfaces.md](07-interfaces.md) (manual encoding rules).

---

## 1. Conventions

- **Base path:** `/api` (or as configured).
- **Auth:** Session cookie or `Authorization: Bearer <token>`. All endpoints except `POST /api/auth/login` require authentication.
- **Boundary 1 (Browser–Server):** Every request is authenticated (where required), authorized (RBAC), CSRF-verified on state-changing methods, and input-validated (type, length, format).
- **Content-Type:** `application/json` for request/response unless noted.
- **Errors:** `4xx`/`5xx` with JSON body `{ "error": "code", "message": "..." }`; validation errors: `{ "errors": [ { "field": "x", "message": "..." } ] }`.

---

## 2. Auth & RBAC

### POST /api/auth/login

| Property | Value |
|----------|--------|
| **Auth** | None (public) |
| **CSRF** | Not required (login form may use double-submit or same-site cookie) |
| **Rate limit** | Yes (e.g. 5/min per IP) |

**Request:**

```json
{
  "email": "admin@example.edu",
  "password": "secret"
}
```

**Validation:** `email` required, valid format; `password` required, non-empty.

**Response 200:**

```json
{
  "user": {
    "id": "uuid",
    "email": "admin@example.edu",
    "role": "admin",
    "first_name": "Jane",
    "last_name": "Admin"
  },
  "session_token": "opaque-token-or-set-cookie"
}
```

**Response 401:** Invalid credentials.

**Auditable events:** None (login failure may be logged for security monitoring; not part of business audit).

---

### POST /api/auth/logout

| Property | Value |
|----------|--------|
| **Auth** | Required (any role) |
| **RBAC** | Any authenticated user |
| **CSRF** | Required |

**Request:** No body (or empty `{}`).

**Response 204:** No content. Session invalidated.

**Auditable events:** None (optional: session.logout with user_id).

---

### RBAC (all other endpoints)

Every endpoint below (except login) must be protected by:

1. **Session/token valid** → 401 if not.
2. **Role allowed** for this endpoint → 403 if not. Role matrix implied by “Roles” column below.
3. **CSRF token** on POST/PATCH/PUT/DELETE → 403 if missing/invalid.
4. **Input validation** as per schemas → 400 with `errors` array.

---

## 3. Scheduling (Admission Periods, Courses, Rooms, Sessions)

### Admission Periods

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | /api/admission-periods | Admin | List periods (optional: status filter) |
| POST | /api/admission-periods | Admin | Create period |
| GET | /api/admission-periods/:id | Admin | Get one |
| PATCH | /api/admission-periods/:id | Admin | Update |
| DELETE | /api/admission-periods/:id | Admin | Delete (only if no dependent courses/sessions) |

**POST body (create/update):**

```json
{
  "name": "2nd Semester AY 2026-2027",
  "start_date": "2026-01-15",
  "end_date": "2026-06-15",
  "status": "draft"
}
```

**Validation:** `name` 1–255 chars; `start_date`/`end_date` valid ISO date, `start_date` <= `end_date`; `status` in (draft, active, closed).

**Auditable events:** `admission_period.create`, `admission_period.update`, `admission_period.delete` — fields: entity_id, (for update: changed_fields or full entity).

---

### Courses

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | /api/courses | Admin | List (optional: admission_period_id) |
| POST | /api/courses | Admin | Create |
| GET | /api/courses/:id | Admin | Get one |
| PATCH | /api/courses/:id | Admin | Update |
| DELETE | /api/courses/:id | Admin | Delete (only if no applications/sessions) |

**POST body:**

```json
{
  "admission_period_id": "uuid",
  "name": "BS Information Technology",
  "code": "BSIT",
  "description": "Bachelor of Science in IT"
}
```

**Validation:** `admission_period_id` required, exists; `name` 1–255; `code` 1–20, unique per period; `description` optional, max 2000.

**Auditable events:** `course.create`, `course.update`, `course.delete`.

---

### Rooms

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | /api/rooms | Admin | List |
| POST | /api/rooms | Admin | Create |
| GET | /api/rooms/:id | Admin | Get one |
| PATCH | /api/rooms/:id | Admin | Update |
| DELETE | /api/rooms/:id | Admin | Delete (only if no sessions) |

**POST body:**

```json
{
  "name": "Room 101",
  "capacity": 40,
  "location_notes": "Building A, 1st floor"
}
```

**Validation:** `name` 1–100; `capacity` integer >= 1; `location_notes` optional, max 1000.

**Auditable events:** `room.create`, `room.update`, `room.delete`.

---

### Exam Sessions

| Method | Endpoint | Roles | Description |
|--------|----------|-------|-------------|
| GET | /api/exam-sessions | Admin, Proctor | List (Admin: all; Proctor: assigned only) |
| POST | /api/exam-sessions | Admin | Create |
| GET | /api/exam-sessions/:id | Admin, Proctor | Get one (Proctor: only if assigned) |
| PATCH | /api/exam-sessions/:id | Admin | Update |
| DELETE | /api/exam-sessions/:id | Admin | Delete (only if no assignments) |

**POST body:**

```json
{
  "course_id": "uuid",
  "room_id": "uuid",
  "proctor_id": "uuid",
  "date": "2026-03-15",
  "start_time": "08:00",
  "end_time": "10:00",
  "status": "scheduled"
}
```

**Validation:** `course_id`, `room_id`, `proctor_id` exist; `proctor_id` is user with role proctor; `date` valid, not in past for create; `start_time`/`end_time` valid time, start < end; `status` in (scheduled, in_progress, completed).

**Auditable events:** `exam_session.create`, `exam_session.update`, `exam_session.delete`.

---

## 4. Applicants & Applications

### POST /api/applicants (create applicant + application in one)

| Property | Value |
|----------|--------|
| **Roles** | Staff (Registrar) |
| **Purpose** | Manual encoding: create Applicant and Application (status = pending_review) in one call. |

**Request:** (aligned to [07-interfaces.md](07-interfaces.md) §2)

```json
{
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "email": "juan@email.com",
  "contact_number": "09171234567",
  "date_of_birth": "2005-03-15",
  "address": "123 Main St",
  "first_course_id": "uuid",
  "second_course_id": "uuid",
  "third_course_id": "uuid"
}
```

**Validation:**  
`first_name`/`last_name` required, 1–100 chars (letters, spaces, hyphens).  
`email` optional; if present, valid email format.  
`contact_number` optional; 7–20 chars, digits/dash/plus.  
`date_of_birth` required, valid date, not future, age >= 15.  
`address` optional, max 500.  
`first_course_id` required, must exist and belong to an active admission period.  
`second_course_id` and `third_course_id` optional; if provided must exist in active period and be distinct from each other and from `first_course_id`.

**Response 201:**  
(Server stores `first_course_id` as `Application.course_id`; `second_course_id` and `third_course_id` as optional FKs.)

```json
{
  "applicant_id": "uuid",
  "application_id": "uuid",
  "status": "pending_review"
}
```

**Auditable events:** `application.create` — entity_type=Application, entity_id=application_id, details: applicant_id, course_id.

---

### GET /api/applications

| Property | Value |
|----------|--------|
| **Roles** | Admin (all); Staff (own encoded only) |

**Query:** `status` (optional: draft, pending_review, approved, rejected, revision_requested), `admission_period_id`, `course_id`, `page`, `limit`.

**Response 200:** List of applications with applicant summary, course, status, reviewed_at. Staff: filter by encoded_by = current user.

**Auditable events:** None (read-only).

---

### GET /api/applications/:id

**Roles:** Admin; Staff (only if they encoded it).  
**Response:** Full application + applicant + course + (if approved) exam_assignment summary.  
**Auditable events:** None.

---

### POST /api/applications/:id/approve

**Roles:** Admin.

**Request (optional assignment in same call):**

```json
{
  "exam_session_id": "uuid",
  "seat_number": "A-01"
}
```

If `exam_session_id` provided: assign to session (capacity check). Capacity check: count existing ExamAssignment for that session; if count >= room.capacity, return 400 "Room at capacity". On success: create ExamAssignment, generate QR (payload + HMAC-SHA256 signature), store on ExamAssignment.

**Response 200:** Updated application (status=approved) and, if assigned, assignment id and qr available.

**Auditable events:** `application.approve` (entity_id=application_id); if assigned: `assignment.create` (entity_id=assignment_id).

---

### POST /api/applications/:id/reject

**Roles:** Admin.

**Request:**

```json
{
  "admin_notes": "Incomplete requirements."
}
```

**Validation:** `admin_notes` optional, max 2000.

**Response 200:** Application status=rejected, reviewed_by, reviewed_at set.

**Auditable events:** `application.reject` — entity_id, details: admin_notes.

---

### POST /api/applications/:id/request-revision

**Roles:** Admin.

**Request:**

```json
{
  "admin_notes": "Please provide correct date of birth."
}
```

**Response 200:** Application status=revision_requested.

**Auditable events:** `application.revision_request` — entity_id, details: admin_notes.

---

### GET /api/exam-assignments/:id/qr (or /api/exam-assignments/:id for full assignment + qr)

**Roles:** Admin, Staff.  
**Purpose:** Used by printable admission slip. Return assignment with `qr_payload` and `qr_signature` (and exam session/room/date/time for display).  
**Auditable events:** None (or optional read log).

---

## 5. QR & Scanning

### POST /api/scan

| Property | Value |
|----------|--------|
| **Roles** | Proctor (and must be assigned to the session in the payload) |
| **Purpose** | Submit scanned QR payload; validate signature, session, room, time window, duplicate; create ScanEntry; return result. |

**Request:**

```json
{
  "qr_payload": "base64-or-json-string-as-sent-in-qr",
  "qr_signature": "hex-string",
  "device_info": "User-Agent string or fingerprint"
}
```

Decode `qr_payload` to get applicant_id, exam_session_id, room_id, schedule_timestamp, generated_at. Recompute HMAC-SHA256 with server secret; compare to `qr_signature` → if mismatch: invalid, reason "Invalid or tampered QR".

Checks:

- Proctor is assigned to exam_session_id (else 403 or invalid result).
- Current time within session date and start_time–end_time window (else invalid, "Outside time window").
- Room/session in payload match assignment (else invalid, "Wrong session" or "Wrong room").
- No existing ScanEntry for this exam_assignment_id with validation_result=valid (else invalid, "Already scanned").

**Response 200 (valid):**

```json
{
  "result": "valid",
  "applicant_name": "Juan Dela Cruz",
  "exam_session_id": "uuid"
}
```

**Response 200 (invalid):**

```json
{
  "result": "invalid",
  "failure_reason": "Already scanned"
}
```

Always create ScanEntry (valid or invalid) with proctor_id, scanned_at, device_info, validation_result, failure_reason.

**Auditable events:** `scan.validate` — entity_type=ScanEntry, entity_id=new_scan_entry_id, details: result, failure_reason, exam_assignment_id, exam_session_id.

---

## 6. Reports

### GET /api/reports/roster/:session_id

**Roles:** Admin; Proctor (only for assigned session_id).  
**Response 200:** List of exam assignments for session with applicant (name, contact), seat_number, assignment id.  
**Auditable events:** None.

---

### GET /api/reports/attendance/:session_id

**Roles:** Admin; Proctor (only for assigned session_id).  
**Response 200:** List of assignments for session with scanned status (scanned_at if any, validation_result).  
**Auditable events:** None.

---

### GET /api/dashboard (or equivalent)

**Roles:** Admin.  
**Response 200:** e.g. `{ "pending_applications_count": 12, "upcoming_sessions": [ ... ] }`.  
**Auditable events:** None.

---

## 7. Audit Log

### GET /api/audit-log

**Roles:** Admin only.

**Query:** `user_id`, `action`, `entity_type`, `entity_id`, `date_from`, `date_to`, `page`, `limit`.

**Response 200:** Paginated list of audit records: user_id, role, action, entity_type, entity_id, ip_address, timestamp, details.

**Auditable events:** None (read-only).

---

## 8. Auditable Events Summary (Phase 1)

| Event name | Entity type | When |
|------------|-------------|------|
| admission_period.create / .update / .delete | AdmissionPeriod | CRUD admission periods |
| course.create / .update / .delete | Course | CRUD courses |
| room.create / .update / .delete | Room | CRUD rooms |
| exam_session.create / .update / .delete | ExamSession | CRUD exam sessions |
| application.create | Application | POST /api/applicants |
| application.approve | Application | POST /api/applications/:id/approve |
| application.reject | Application | POST /api/applications/:id/reject |
| application.revision_request | Application | POST /api/applications/:id/request-revision |
| assignment.create | ExamAssignment | On approve with exam_session_id |
| scan.validate | ScanEntry | POST /api/scan |

Each audit record: user_id, role, action, entity_type, entity_id, ip_address, timestamp, details (JSON). Per [05-security-controls.md](05-security-controls.md) SC-10.
