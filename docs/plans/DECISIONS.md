# SecureCAT — Architecture Decision Records (ADRs)

Lightweight log of key decisions made during planning and development. Each entry is append-only: superseded decisions are marked but never deleted.

---

## ADR-001: Registrar-Driven Intake First

**Date:** 2026-02-08
**Status:** Accepted

**Context:** The ISPSC registrar owns the application process. Applicants apply at the registrar's office, not directly in SecureCAT. Building examinee self-service first would duplicate effort and delay the core operational workflow.

**Decision:** Phase 1 supports only staff/registrar manual encoding of applicant data. CSV bulk import and API intake are added in Phase 2. Examinee self-service registration is deferred to a future phase (beyond Phase 4 scope).

**Consequences:**
- Phase 1 has no examinee-facing portal for application submission.
- Examinee interaction is limited to receiving a printed QR admission slip and (later) viewing results.
- The data model still includes the Examinee role so it can be activated later without schema changes.

---

## ADR-002: Framework-Agnostic Architecture Documentation

**Date:** 2026-02-08
**Status:** Accepted

**Context:** The original proposal listed Laravel and Django as options but recommended Laravel. The team has not finalized the stack. Coupling planning docs to a specific framework creates rework if the choice changes.

**Decision:** Architecture docs describe services, data model, APIs, and security controls without referencing a specific framework. Technology constraints (PostgreSQL, Redis, bcrypt, HMAC-SHA256, TLS) are specified; framework choice is deferred to the start of Phase 1 development.

**Consequences:**
- Architecture docs are longer-lived and reusable.
- The team must make a framework decision before coding starts (record as ADR-003 when decided).

---

## ADR-003: Laravel + TALL Stack

**Date:** 2026-02-08
**Status:** Accepted

**Context:** Phase 1 development requires a concrete framework choice (deferred in ADR-002). The team selected Laravel and the TALL stack for implementation.

**Decision:** Use Laravel as the backend framework and the TALL stack for the full-stack implementation:
- **T**ailwind CSS — utility-first styling
- **A**lpine.js — lightweight client-side interactivity
- **L**aravel — backend framework (routing, Eloquent ORM, auth, queues, etc.)
- **L**ivewire — server-rendered dynamic components without writing JavaScript

**Consequences:**
- Architecture docs and Phase 1 implementation will use Laravel conventions (blade, Eloquent, migrations, etc.).
- UI components will be built with Livewire + Alpine + Tailwind.
- This satisfies ADR-002’s requirement to finalize the stack before coding.

---

## ADR-004: Iterative Phase Delivery Model

**Date:** 2026-02-08
**Status:** Accepted

**Context:** The original `icat-system.md` used a waterfall SDLC (plan → analyze → design → develop → test → deploy in one pass). This risks delivering nothing usable until the end of the timeline.

**Decision:** Restructure into four delivery phases (Core Ops → Intake Expansion → Scoring/Results → Hardening). Each phase produces a demonstrable, usable system slice with its own acceptance criteria.

**Consequences:**
- Stakeholders get early feedback opportunities.
- Phase 1 deliberately omits scoring, results, and advanced audit features — these ship later.
- Each phase sub-plan must define its own scope, non-goals, and acceptance criteria.

---

## ADR-005: Application Intake Integration Options

**Date:** 2026-02-08
**Status:** Accepted

**Context:** The registrar may send application data via different channels depending on volume and technical capability.

**Decision:** Support three intake mechanisms, introduced incrementally:
1. **Phase 1** — Manual encoding UI (staff types applicant data into a form).
2. **Phase 2** — CSV bulk import with validation + "review before commit" screen.
3. **Phase 2** — Optional REST API endpoint with the same review-before-commit semantics.

All three channels feed the same approval queue. The admin always reviews before an application becomes active.

**Consequences:**
- Phase 1 is simple (one form).
- Phase 2 must define CSV format, validation rules, and API contract (see `docs/architecture/07-interfaces.md`).
- The review-before-commit pattern ensures data quality regardless of intake channel.
