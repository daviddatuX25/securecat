# Phase 1 — Stories

> User stories mapped to the Phase 1 demo flow, with traceability to architecture and security.

**Phase:** 1  
**Reference:** [PHASE-1-scope-freeze.md](../phases/PHASE-1-scope-freeze.md), [PHASE-1-core-ops.md](../phases/PHASE-1-core-ops.md)

---

## Story Format

Each story includes:
- **Trace:** Components ([03-components.md](../../architecture/03-components.md)), Entities ([04-data-model.md](../../architecture/04-data-model.md)), Security controls ([05-security-controls.md](../../architecture/05-security-controls.md)), IAS principles (as named in 05).
- **Tags:** Phase=1, Role(s)=Admin|Registrar|Proctor|Applicant (as relevant).

---

## E1 — Auth & RBAC

### S1.1 — User can log in and receive a session

| Field | Value |
|-------|--------|
| **As a** | Admin, Staff, or Proctor |
| **I want** | to log in with email and password and receive a secure session |
| **So that** | I can perform role-appropriate actions |
| **Phase** | 1 |
| **Roles** | Admin, Registrar (Staff), Proctor |
| **Components** | Auth Service, RBAC Middleware |
| **Entities** | User |
| **Security controls** | SC-01, SC-02, SC-03, SC-04, SC-12 |
| **IAS** | Confidentiality, Availability |

---

### S1.2 — User can log out and session expires correctly

| Field | Value |
|-------|--------|
| **As a** | logged-in user |
| **I want** | to log out and have my session invalidated |
| **So that** | no one else can use my session |
| **Phase** | 1 |
| **Roles** | Admin, Registrar, Proctor |
| **Components** | Auth Service, RBAC Middleware |
| **Entities** | User |
| **Security controls** | SC-03 |
| **IAS** | Confidentiality |

---

### S1.3 — Access is restricted by role (RBAC)

| Field | Value |
|-------|--------|
| **As a** | system |
| **I want** | every route/endpoint to check the user's role and reject unauthorized access |
| **So that** | Staff cannot access admin-only features and Proctor cannot encode applicants |
| **Phase** | 1 |
| **Roles** | Admin, Registrar, Proctor |
| **Components** | RBAC Middleware |
| **Entities** | User |
| **Security controls** | SC-01 |
| **IAS** | Confidentiality |

---

## E2 — Scheduling & Setup

### S2.1 — Admin can create and manage admission periods

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to create admission periods (name, start/end dates, status) and list/edit them |
| **So that** | exams can be organized by semester/period |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Scheduling Engine |
| **Entities** | AdmissionPeriod, User |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

### S2.2 — Admin can create and manage courses per period

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to add courses (name, code, description) to an admission period |
| **So that** | applicants can be assigned to the correct course |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Scheduling Engine |
| **Entities** | Course, AdmissionPeriod |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

### S2.3 — Admin can create and manage rooms

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to define rooms (name, capacity, location notes) |
| **So that** | exam sessions can be assigned to physical rooms with capacity limits |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Scheduling Engine |
| **Entities** | Room |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

### S2.4 — Admin can create exam sessions and assign proctors

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to create exam sessions (course, room, date, time, duration) and assign a proctor |
| **So that** | applicants can be scheduled and proctors know their sessions |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Scheduling Engine |
| **Entities** | ExamSession, Course, Room, User |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

## E3 — Application Lifecycle

### S3.1 — Staff can encode an applicant and create a pending application

| Field | Value |
|-------|--------|
| **As a** | Staff (Registrar) |
| **I want** | to fill a form with applicant details and up to three preferred course options and submit |
| **So that** | the application appears in the admin approval queue as pending_review |
| **Phase** | 1 |
| **Roles** | Registrar |
| **Components** | Application Management, Intake Gateway (manual only) |
| **Entities** | Applicant, Application, Course, AdmissionPeriod, User |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

### S3.2 — Admin can review and approve, reject, or request revision

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to view the approval queue and approve, reject, or request revision (with notes) |
| **So that** | only approved applications get assigned and receive a QR slip |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Application Management |
| **Entities** | Application, User |
| **Security controls** | SC-01, SC-07, SC-08, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability |

---

### S3.3 — Admin can assign approved applicant to session (with capacity check)

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to assign an approved applicant to an exam session and room |
| **So that** | the system generates a signed QR and respects room capacity |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Scheduling Engine, Application Management, QR Service |
| **Entities** | ExamAssignment, Application, ExamSession, Room |
| **Security controls** | SC-01, SC-07, SC-08, SC-09, SC-10 |
| **IAS** | Confidentiality, Integrity, Accountability, Non-Repudiation |

---

## E4 — QR & Exam-Day Scanning

### S4.1 — System generates signed QR and printable admission slip

| Field | Value |
|-------|--------|
| **As a** | Admin or Staff |
| **I want** | to open a printable admission slip for an assigned applicant |
| **So that** | the slip shows a valid HMAC-SHA256 signed QR and exam details |
| **Phase** | 1 |
| **Roles** | Admin, Registrar |
| **Components** | QR Service |
| **Entities** | ExamAssignment |
| **Security controls** | SC-01, SC-09, SC-10 |
| **IAS** | Integrity, Non-Repudiation, Accountability |

---

### S4.2 — Proctor can scan QR and get valid/invalid result

| Field | Value |
|-------|--------|
| **As a** | Proctor |
| **I want** | to scan a candidate's QR (camera or manual entry) and see pass/fail with reason |
| **So that** | only valid, correct-session, within-time-window scans are accepted; anomalies are shown |
| **Phase** | 1 |
| **Roles** | Proctor |
| **Components** | QR Service |
| **Entities** | ScanEntry, ExamAssignment, ExamSession, User |
| **Security controls** | SC-01, SC-07, SC-09, SC-10, SC-11 |
| **IAS** | Integrity, Non-Repudiation, Accountability |

---

### S4.3 — Duplicate and wrong-session scans are detected and logged

| Field | Value |
|-------|--------|
| **As a** | Proctor |
| **I want** | duplicate scans and wrong room/session/time to show a clear failure reason |
| **So that** | exam-day integrity is enforced and all attempts are logged |
| **Phase** | 1 |
| **Roles** | Proctor |
| **Components** | QR Service |
| **Entities** | ScanEntry, ExamAssignment |
| **Security controls** | SC-09, SC-11 |
| **IAS** | Integrity, Non-Repudiation |

---

## E5 — Reports & Visibility

### S5.1 — Admin and Proctor can view roster per session

| Field | Value |
|-------|--------|
| **As a** | Admin or Proctor |
| **I want** | to view the list of assigned applicants for an exam session |
| **So that** | I can verify who is expected and (proctor) who has been scanned |
| **Phase** | 1 |
| **Roles** | Admin, Proctor |
| **Components** | Scheduling Engine (read), Application Management (read) |
| **Entities** | ExamAssignment, ExamSession, Applicant, Application |
| **Security controls** | SC-01 |
| **IAS** | Confidentiality |

---

### S5.2 — Admin and Proctor can view attendance per session

| Field | Value |
|-------|--------|
| **As a** | Admin or Proctor |
| **I want** | to see which applicants have been scanned (attendance) for a session |
| **So that** | I can report and follow up on no-shows |
| **Phase** | 1 |
| **Roles** | Admin, Proctor |
| **Components** | QR Service (read), Scheduling Engine |
| **Entities** | ScanEntry, ExamAssignment, ExamSession |
| **Security controls** | SC-01 |
| **IAS** | Confidentiality |

---

### S5.3 — Admin dashboard shows approval queue and upcoming sessions

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | the dashboard to show pending approval count and upcoming sessions |
| **So that** | I can prioritize review and planning |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Application Management, Scheduling Engine |
| **Entities** | Application, ExamSession |
| **Security controls** | SC-01 |
| **IAS** | Confidentiality |

---

## E6 — Audit & Compliance

### S6.1 — Every mutating action produces an audit record

| Field | Value |
|-------|--------|
| **As a** | system |
| **I want** | every create/update/delete on core entities to write an audit log entry |
| **So that** | we have accountability (user, role, action, entity, timestamp, IP) |
| **Phase** | 1 |
| **Roles** | — |
| **Components** | Audit Logger |
| **Entities** | AuditLog |
| **Security controls** | SC-10 |
| **IAS** | Accountability |

---

### S6.2 — Admin can browse and filter audit log

| Field | Value |
|-------|--------|
| **As a** | Admin |
| **I want** | to open the audit log and filter by user, action, entity, date range |
| **So that** | I can investigate and comply with audit requirements |
| **Phase** | 1 |
| **Roles** | Admin |
| **Components** | Audit Logger |
| **Entities** | AuditLog |
| **Security controls** | SC-01, SC-10 |
| **IAS** | Confidentiality, Accountability |
