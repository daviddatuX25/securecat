# SecureCAT — Architecture Overview

> Framework-agnostic system architecture for the Secure College Admission Testing platform.

---

## Purpose

This architecture pack describes SecureCAT's structure using a layered approach inspired by the [C4 model](https://c4model.com/):

| Level | Document | What It Shows |
|-------|----------|---------------|
| 1 — Context | [01-context.md](01-context.md) | System boundary, actors, external systems |
| 2 — Containers | [02-containers.md](02-containers.md) | Deployable units: web app, API, databases, file store |
| 3 — Components | [03-components.md](03-components.md) | Logical modules inside the API/app: Auth, Applications, QR, Scoring, etc. |
| Data | [04-data-model.md](04-data-model.md) | Core entities, relationships, lifecycle states |
| Security | [05-security-controls.md](05-security-controls.md) | IAS mapping, threat model summary, control catalog |
| Data Flow | [06-dfd-trust-boundaries.md](06-dfd-trust-boundaries.md) | Data Flow Diagrams with trust boundary annotations |
| Interfaces | [07-interfaces.md](07-interfaces.md) | External integration contracts: CSV format, API intake, review workflow |

---

## Guiding Principles

1. **Security by design** — IAS controls (confidentiality, integrity, availability, accountability, non-repudiation) shape every decision.
2. **Framework-agnostic** — documents describe what the system does, not how a specific framework implements it. Technology constraints are listed; framework choice is deferred (see [DECISIONS.md](../plans/DECISIONS.md) ADR-002).
3. **Phase-aligned** — the architecture supports incremental delivery. Each component notes which phase introduces it.
4. **Registrar-driven intake** — the registrar office is the primary source of application data. Self-service is a future extension.

---

## Technology Constraints

These are binding regardless of framework choice:

| Concern | Constraint |
|---------|-----------|
| Transport encryption | HTTPS with TLS 1.2+ (TLS 1.3 preferred) |
| Password storage | bcrypt (cost >= 12) or argon2id |
| QR code signatures | HMAC-SHA256 with server-side secret key |
| Primary database | PostgreSQL 15+ (or equivalent RDBMS with encryption-at-rest and row-level security) |
| Session / cache store | Redis 7+ (or equivalent in-memory store) |
| File storage | Encrypted filesystem or object storage; virus scanning on upload |
| Audit log hashing | SHA-256 for hash-chained records (Phase 4) |
| MFA | TOTP per RFC 6238 (Phase 4) |

---

## Cross-References

- **Master Plan:** [MASTER.md](../plans/MASTER.md)
- **Phase sub-plans:** [phases/](../plans/phases/)
- **Decision log:** [DECISIONS.md](../plans/DECISIONS.md)
- **Traceability:** [TRACEABILITY.md](../plans/TRACEABILITY.md)
