# SecureCAT — Traceability Matrix

> Maps every module from `icat-system.md` to its delivery phase and corresponding architecture section. Nothing is lost.

---

## Module-to-Phase Mapping

The table below maps the eight modules defined in `icat-system.md` Section 4.1 to the delivery phases in the [Master Plan](MASTER.md).

| # | Module (icat-system.md) | Phase 1 | Phase 2 | Phase 3 | Phase 4 | Architecture Doc |
|---|------------------------|---------|---------|---------|---------|-----------------|
| M1 | **User Authentication & RBAC** | Auth, login, session, RBAC for Admin/Staff/Proctor. Password hashing. | — | — | MFA (TOTP), device registration, recovery codes. | [03-components.md](../architecture/03-components.md) (Auth Service, RBAC Middleware) |
| M2 | **Application Management** | Manual encoding (staff form). Approval workflow (approve/reject/revision). Application state machine. | CSV bulk import. API intake. Document upload + verification. Notifications. Enhanced audit diffs. | — | — | [03-components.md](../architecture/03-components.md) (Application Management, Intake Gateway), [07-interfaces.md](../architecture/07-interfaces.md) |
| M3 | **Exam Scheduling & Room Assignment** | Admission periods, courses, rooms, exam sessions. Proctor assignment. Applicant-to-session assignment with capacity check. | — | — | — | [03-components.md](../architecture/03-components.md) (Scheduling Engine) |
| M4 | **QR Code Generation & Validation** | QR generation (HMAC-SHA256 signed). Online proctor scanning. Anomaly detection (wrong room/session, duplicate, tampered). Entry logging. | — | — | Offline scanning (cached roster + sync). | [03-components.md](../architecture/03-components.md) (QR Service), [06-dfd-trust-boundaries.md](../architecture/06-dfd-trust-boundaries.md) |
| M5 | **Secure Scoring System** | — | — | OMR CSV import with checksum. Answer key management (encrypted). Auto score calculation. Manual entry/correction with justification. Score history. | — | [03-components.md](../architecture/03-components.md) (Scoring Module), [07-interfaces.md](../architecture/07-interfaces.md) (OMR CSV format) |
| M6 | **Result Management & Release** | — | — | Controlled release workflow. Score review dashboard. Examinee result portal. PDF result slip. Access logging. | — | [03-components.md](../architecture/03-components.md) (Result Release) |
| M7 | **Comprehensive Audit Logging** | Foundational: user, action, entity, timestamp, IP. Admin can browse logs. | Before/after value diffs on updates. | Score-specific history tracking. | Hash-chained records. Append-only enforcement. Verification tooling. Archival. 7-year retention. | [03-components.md](../architecture/03-components.md) (Audit Logger), [04-data-model.md](../architecture/04-data-model.md) (AuditLog entity) |
| M8 | **Admin Dashboard & Reporting** | Minimal: approval queue count, upcoming sessions, roster, attendance list. | Import batch status. Notification badge. | Score distribution, pass/fail counts, outlier detection. | Security alerts dashboard. Backup status. Audit chain status. | (Distributed across phase sub-plans) |

---

## Role Activation by Phase

| Role | Phase 1 | Phase 2 | Phase 3 | Phase 4 |
|------|---------|---------|---------|---------|
| **Administrator** | Full access to setup, approval, audit | + Import review, document verification, notification management | + Score import, result release | + MFA, device management, privacy, backup |
| **Staff** | Encode applicants, view own applications | + CSV import, document upload, respond to revision requests | — | — |
| **Proctor** | Scan QR (online), view roster, view attendance | — | — | + Offline scanning, device registration |
| **Examinee** | (Not active — receives printed QR slip) | (Not active) | View results, download PDF slip | — |

---

## Security Control Activation by Phase

Full control catalog is in [05-security-controls.md](../architecture/05-security-controls.md). Summary:

| Phase | Controls Introduced |
|-------|-------------------|
| **Phase 1** | SC-01 through SC-12: RBAC, password hashing, sessions, TLS, encrypted DB, input validation, CSRF, QR signing, foundational audit, scan logging, security headers |
| **Phase 2** | SC-13 through SC-18: Document access control, file type validation, virus scanning, API authentication, audit diffs, intake tracking |
| **Phase 3** | SC-19 through SC-24: Answer key encryption, score checksum, correction justification, pre-release hiding, result access logging, score history |
| **Phase 4** | SC-25 through SC-33: MFA, device registration, hash-chained audit, append-only audit, offline roster encryption, intrusion prevention, backup verification, data anonymization, consent management |

---

## Data Model Growth by Phase

| Phase | Entities Introduced | Entities Modified |
|-------|-------------------|-------------------|
| **Phase 1** | User, AdmissionPeriod, Course, Room, ExamSession, Applicant, Application, ExamAssignment, ScanEntry, AuditLog | — |
| **Phase 2** | Document, ImportBatch, ImportBatchRow, Notification | Application (+intake_source), AuditLog (+before_value, +after_value) |
| **Phase 3** | Score, ScoreHistory, ScoreImportBatch (reuses ImportBatch), AnswerKey, ResultRelease, ResultAccess | ExamSession (+scoring_status, +pass_threshold) |
| **Phase 4** | Device, OfflineScanQueue, ConsentRecord, DataRetentionPolicy, AuditArchive | User (+mfa_secret, +mfa_enabled, +recovery_codes), Applicant (+is_anonymized), AuditLog (+previous_hash, +current_hash) |

Full entity catalog: [04-data-model.md](../architecture/04-data-model.md)

---

## Feature Source → Architecture Document Cross-Reference

| icat-system.md Section | Topic | Architecture Document |
|------------------------|-------|----------------------|
| Section 3 (Objectives) | IAS security objectives | [05-security-controls.md](../architecture/05-security-controls.md) Section 1 |
| Section 4.1 (Modules) | Feature scope per module | This file (above table) |
| Section 5.1 (Architecture) | Three-layer architecture | [02-containers.md](../architecture/02-containers.md) |
| Section 5.2 (Security Architecture) | Defense-in-depth | [05-security-controls.md](../architecture/05-security-controls.md) Section 3 |
| Section 5.3 (Access Control Matrix) | RBAC table | [03-components.md](../architecture/03-components.md) (RBAC Middleware) |
| Section 5.3 (Cryptographic Controls) | QR signing, checksums, hash-chain | [05-security-controls.md](../architecture/05-security-controls.md) Sections 1-2 |
| Section 5.4 (System Workflow) | Application lifecycle | [06-dfd-trust-boundaries.md](../architecture/06-dfd-trust-boundaries.md) Section 4 |
| Section 5.5 (Technology Stack) | Stack choices | [00-overview.md](../architecture/00-overview.md) (Technology Constraints) |
| Section 6 (System Users) | Role definitions | [01-context.md](../architecture/01-context.md) (Actors) |
| Section 7 (Development Methodology) | SDLC phases | Replaced by [MASTER.md](MASTER.md) phase model |
| Section 8 (Hardware/Software) | Requirements | [00-overview.md](../architecture/00-overview.md) (constraints); details retained in icat-system.md |
| Section 9 (Timeline) | Week-by-week plan | Replaced by phase sub-plans in [phases/](phases/) |
