# Phase 1 — Scope Freeze

> Single end-to-end demo flow, in/out of scope, and minimum requirements for a shippable Phase 1 product.

**References:** [MASTER.md](../MASTER.md), [PHASE-1-core-ops.md](PHASE-1-core-ops.md), [TRACEABILITY.md](../TRACEABILITY.md), [04-data-model.md](../../architecture/04-data-model.md), [05-security-controls.md](../../architecture/05-security-controls.md).

---

## 1. Single End-to-End Demo Flow (Thin Vertical Slice)

One path that must work from login to report:

| Step | Actor | Action | Outcome |
|------|--------|--------|---------|
| 1 | Admin | Log in | Session established |
| 2 | Admin | Create admission period (name, start/end dates) | Period in draft/active |
| 3 | Admin | Add course to period (name, code) | Course linked to period |
| 4 | Admin | Create room (name, capacity) | Room available |
| 5 | Admin | Create exam session (course, room, date, time, duration), assign proctor | Session scheduled |
| 6 | Staff | Log in, encode one applicant (name, contact, DoB, course) | Applicant + Application in `pending_review` |
| 7 | Admin | Open approval queue, approve that application, assign to session | Application `approved`, ExamAssignment created, QR generated |
| 8 | Admin or Staff | Open printable admission slip for assignment | QR visible, signature valid |
| 9 | Proctor | Log in, select assigned session, scan applicant QR | Green result, ScanEntry logged |
| 10 | Proctor | Scan same QR again (or wrong-session QR) | Red result, reason shown, ScanEntry logged |
| 11 | Admin or Proctor | View roster for session; view attendance for session | Roster and attendance lists correct |
| 12 | Admin | Open audit log | Entries for period create, encode, approve, assignment, scan |
| 13 | — | Staff accesses `/admin/audit-log`; Proctor accesses `/staff/encode` | 403 (or equivalent) |

---

## 2. In Scope vs Out of Scope

| In scope | Out of scope |
|----------|--------------|
| Auth: login, logout, session, RBAC (Admin / Staff / Proctor) | Examinee login/portal |
| Admission period, course, room, exam session CRUD; proctor assignment | CSV/API intake, document upload |
| Manual applicant encoding (single form → Applicant + Application) | Notifications (email/in-app) |
| Approval workflow: approve, reject, request revision | OMR scoring, result release |
| Assign approved applicant to session (capacity check) | Offline scanning, MFA, device registration |
| QR generation (HMAC-SHA256) and printable admission slip | Hash-chained / append-only audit |
| Proctor scan (camera/input) → validate → log; anomaly messages | Before/after audit diffs |
| Roster and attendance reports per session | Dedicated audit DB instance |
| Foundational audit log (user, role, action, entity, timestamp, IP) | Privacy/anonymization workflows |
| RBAC enforced on every route/endpoint | |

---

## 3. Minimum Data Model Required

Only entities and fields needed for the demo. Canonical source: [04-data-model.md](../../architecture/04-data-model.md) Phase 1 columns.

| Entity | Minimum fields (Phase 1 only) |
|--------|--------------------------------|
| **User** | id, email, password_hash, role (admin|staff|proctor|examinee), first_name, last_name, is_active, created_at, updated_at |
| **AdmissionPeriod** | id, name, start_date, end_date, status, created_by, created_at |
| **Course** | id, admission_period_id, name, code, description, created_at |
| **Room** | id, name, capacity, location_notes, created_at |
| **ExamSession** | id, course_id, room_id, proctor_id, date, start_time, end_time, status, created_at |
| **Applicant** | id, first_name, last_name, email, contact_number, date_of_birth, address, encoded_by, created_at |
| **Application** | id, applicant_id, course_id, second_course_id, third_course_id, admission_period_id, status, admin_notes, reviewed_by, reviewed_at, created_at, updated_at |
| **ExamAssignment** | id, application_id, exam_session_id, seat_number, qr_payload, qr_signature, assigned_at |
| **ScanEntry** | id, exam_assignment_id, proctor_id, scanned_at, device_info, validation_result, failure_reason |
| **AuditLog** | id, user_id, role, action, entity_type, entity_id, ip_address, timestamp, details |

Note: Omit Phase 2+ columns (e.g. `Application.intake_source`, `Applicant.photo_path`, `AuditLog.before_value`/`after_value`/`previous_hash`/`current_hash`).

---

## 4. Minimum Security Controls Required

Subset of [05-security-controls.md](../../architecture/05-security-controls.md) for Phase 1:

| ID | Control | IAS principle(s) |
|----|---------|-------------------|
| SC-01 | RBAC on every route/endpoint | Confidentiality |
| SC-02 | Password hashing (bcrypt/argon2) | Confidentiality |
| SC-03 | Session management (Redis, TTL) | Confidentiality, Availability |
| SC-04 | HTTPS / TLS | Confidentiality |
| SC-05 | Encrypted DB connections | Confidentiality |
| SC-06 | Data encryption at rest (sensitive fields) | Confidentiality |
| SC-07 | Input validation (all user input) | Integrity |
| SC-08 | CSRF protection (state-changing requests) | Integrity |
| SC-09 | QR signing (HMAC-SHA256) and verification | Integrity, Non-Repudiation |
| SC-10 | Foundational audit logging (user, role, action, entity, timestamp, IP) | Accountability |
| SC-11 | Scan entry logging (immutable per scan) | Non-Repudiation |
| SC-12 | Security headers (HSTS, CSP, X-Frame-Options, etc.) | Integrity, Confidentiality |

---

## 5. Phase 1 Definition of Done

Tied to [MASTER.md](../MASTER.md) §5.

- **Functional:** Demo flow above is demonstrable end-to-end; acceptance criteria in [PHASE-1-core-ops.md](PHASE-1-core-ops.md) §7 pass; no critical/high bugs open.
- **Security:** RBAC enforced; audit record for every mutating action in scope; no hard-coded secrets (env config); HTTPS only; input validation on all user data.
- **Quality:** Core business logic covered by automated tests (unit or integration); manual smoke script passed; code reviewed by at least one other team member.
