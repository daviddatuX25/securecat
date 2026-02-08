# SecureCAT Master Plan

> Secure College Admission Testing — an IAS-focused examination management platform.

---

## 1. Product Intent

SecureCAT manages the **complete lifecycle of physical college entrance examinations** — from application intake through result release — with Information Assurance & Security (IAS) principles embedded at every layer.

The system is **registrar/staff-driven first**: application data originates from the registrar's office (manual entry, CSV import, or API) and flows through admin review before an examinee ever interacts with the platform. Examinee self-service capabilities are planned but deferred until the core operational workflow is proven.

### 1.1 Core Value Propositions

| # | Value | How |
|---|-------|-----|
| 1 | Exam-day identity assurance | Cryptographically signed QR codes, proctor scanning, anomaly alerts |
| 2 | Tamper-evident records | Audit logging on every mutation; hash-chained immutable log (Phase 4) |
| 3 | Controlled result release | Scores imported with integrity checks, released only on explicit admin authorization |
| 4 | Role-enforced least privilege | RBAC across Admin / Staff / Proctor / Examinee with per-action permissions |
| 5 | Operational efficiency | Replaces paper-based workflows; automated scheduling, bulk import, digital notifications |

### 1.2 Non-Goals (Excluded Scope)

These items are explicitly out of scope for all phases (see `icat-system.md` Section 4.2 for rationale):

- Online examination delivery / computer-based testing (CBT)
- Payment processing / fee collection
- AI-based course recommendation engine
- Native mobile applications (PWA covers mobile needs)
- SMS notifications (email + portal only)
- LMS integration
- Advanced analytics / ML-based predictions
- OCR or advanced document management
- Direct SIS integration (export-only is provided)

---

## 2. Release Philosophy

**Every phase ships a working product.** Stakeholders can demo, test, and use the system at the end of each phase — even though later phases add critical features.

Principles:

1. **Vertical slices** — each phase delivers end-to-end user journeys, not isolated backend modules.
2. **Security is incremental but never absent** — Phase 1 includes auth, RBAC, signed QR, and basic audit logging. Later phases raise the assurance bar (MFA, hash-chained logs, offline capability).
3. **Additive, not rewriting** — later phases extend the data model and UI; they do not replace Phase 1 code.
4. **Registrar-first intake** — the registrar/staff encodes applications in Phase 1. Bulk import, API intake, and eventually examinee self-service are layered on in Phase 2+.

---

## 3. Phase Index

| Phase | Name | Outcome | Sub-Plan |
|-------|------|---------|----------|
| 1 | **Core Operations** | Staff encodes applicants, admin approves, QR issued, proctor scans on exam day | [PHASE-1-core-ops.md](phases/PHASE-1-core-ops.md) |
| 2 | **Intake Expansion + Documents** | CSV/API bulk import, document upload & verification, notifications | [PHASE-2-intake-and-docs.md](phases/PHASE-2-intake-and-docs.md) |
| 3 | **Scoring + Results** | OMR score import, score review, controlled result release, PDF slips | [PHASE-3-scoring-and-results.md](phases/PHASE-3-scoring-and-results.md) |
| 4 | **Hardening + Compliance** | Offline scanning, MFA, hash-chained audit, backup drills, incident response | [PHASE-4-hardening-and-compliance.md](phases/PHASE-4-hardening-and-compliance.md) |

---

## 4. Cross-Cutting Requirements (All Phases)

These requirements apply from Phase 1 onward and are deepened in later phases.

### 4.1 IAS Principles

| Principle | Phase 1 Baseline | Raised In |
|-----------|-------------------|-----------|
| **Confidentiality** | RBAC + HTTPS/TLS + encrypted DB connections | Phase 4: MFA, device registration |
| **Integrity** | Input validation, CSRF protection, signed QR codes | Phase 3: checksum-verified score import; Phase 4: hash-chained audit |
| **Availability** | Standard server deployment, session management | Phase 4: offline scanning, backup/restore drills, load testing |
| **Accountability** | Foundational audit log (user, action, timestamp, IP) | Phase 2: before/after values; Phase 4: append-only + hash-chained log |
| **Non-Repudiation** | HMAC-SHA256 signed QR, proctor entry log | Phase 4: full cryptographic audit chain |

### 4.2 Auditability

- Every create / update / delete on core entities must produce an audit record.
- Audit records include: `user_id`, `role`, `action`, `entity_type`, `entity_id`, `timestamp`, `ip_address`.
- Phase 2 adds `before_value` / `after_value` diffs.
- Phase 4 adds hash-chaining and append-only storage.

### 4.3 Data Privacy

- Collect only data necessary for admission (Data Privacy Act / RA 10173 alignment).
- Sensitive fields (e.g., personal info) encrypted at rest from Phase 1.
- Consent captured at account creation / application encoding.
- Retention and deletion policies defined in Phase 4.

### 4.4 Technology Constraints

The architecture is **framework-agnostic** (see [Architecture Pack](../architecture/00-overview.md)). Any stack that satisfies these constraints is acceptable:

- Server-rendered or SPA web frontend (responsive, mobile-friendly)
- RESTful or RPC-style API backend
- PostgreSQL (or equivalent RDBMS with encryption-at-rest support)
- Redis (or equivalent) for sessions/cache
- HTTPS/TLS 1.2+ enforced for all traffic
- bcrypt (or argon2) for password hashing
- HMAC-SHA256 for QR code signatures

---

## 5. Definition of Done (Per Phase)

A phase is **done** when all of the following are satisfied:

### Functional

- [ ] All user journeys listed in the phase sub-plan are demonstrable end-to-end.
- [ ] Acceptance criteria in the sub-plan pass.
- [ ] No critical or high-severity bugs remain open.

### Security

- [ ] RBAC enforced: every endpoint/page checks permissions before acting.
- [ ] Audit log records generated for every mutating action in scope.
- [ ] No secrets hard-coded; configuration via environment variables.
- [ ] HTTPS enforced; no mixed content.
- [ ] Input validation on all user-supplied data.

### Quality

- [ ] Core business logic covered by automated tests (unit or integration).
- [ ] Manual smoke-test script executed successfully.
- [ ] Code reviewed by at least one other team member.

---

## 6. Supporting Documents

| Document | Purpose |
|----------|---------|
| [DECISIONS.md](DECISIONS.md) | Lightweight Architecture Decision Records (ADRs) |
| [TRACEABILITY.md](TRACEABILITY.md) | Maps `icat-system.md` modules to phases and architecture sections |
| [Architecture Pack](../architecture/00-overview.md) | Framework-agnostic system architecture (C4 style) |

---

## 7. Feature Source of Truth

The original feature scope and IAS rationale live in [`icat-system.md`](../../icat-system.md). This master plan **delegates** to phase sub-plans for delivery order and acceptance criteria. The [TRACEABILITY.md](TRACEABILITY.md) file maps every module from `icat-system.md` to its delivery phase so nothing is lost.
