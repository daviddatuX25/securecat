# SecureCAT — Epics

> High-level capability groupings. Phase 1 epics align to the demo flow and [TRACEABILITY.md](../TRACEABILITY.md).

---

## Epic Index

| ID | Epic | Phase(s) | Description |
|----|------|----------|-------------|
| E1 | **Auth & RBAC** | 1 | Login, logout, session management, role-based access for Admin/Staff/Proctor. |
| E2 | **Scheduling & Setup** | 1 | Admission periods, courses, rooms, exam sessions, proctor assignment. |
| E3 | **Application Lifecycle** | 1, 2 | Encode applicant, approval workflow (approve/reject/revision), assignment to session. |
| E4 | **QR & Exam-Day Scanning** | 1 | Signed QR generation, admission slip, proctor scan validation, anomaly detection, scan logging. |
| E5 | **Reports & Visibility** | 1 | Roster per session, attendance per session, admin dashboard counts. |
| E6 | **Audit & Compliance** | 1–4 | Audit logging (foundational in P1), browse logs; later diffs, hash-chain, archival. |
| E7 | **Intake Expansion** | 2 | CSV import, API intake, document upload, notifications. |
| E8 | **Scoring & Results** | 3 | OMR import, score review, result release, examinee result view, PDF slip. |
| E9 | **Hardening & Compliance** | 4 | Offline scanning, MFA, device registration, hash-chained audit, backup, privacy. |

---

## Phase 1 Epic Scope (Summary)

- **E1:** Full (login, logout, session, RBAC).
- **E2:** Full (periods, courses, rooms, sessions, proctor assignment).
- **E3:** Manual encoding + approval + assignment only (no CSV/API/docs).
- **E4:** Full (QR generation, slip, online scan, validation, scan log).
- **E5:** Roster + attendance + minimal dashboard.
- **E6:** Foundational audit log + admin browse only.

Stories and tasks for Phase 1 are in [PHASE-1-STORIES.md](PHASE-1-STORIES.md) and [PHASE-1-TASKS.md](PHASE-1-TASKS.md).
