# Phase 0 — Tasks

> Project bootstrap: Laravel + TALL stack setup. Prerequisite for [PHASE-1-TASKS.md](PHASE-1-TASKS.md).

**Phase:** 0  
**Reference:** [PHASE-0-BOOTSTRAP.md](../phases/PHASE-0-BOOTSTRAP.md)

---

## Task Index

| Task ID | Summary |
|---------|---------|
| T0.1 | Create Laravel project (composer create-project) |
| T0.2 | Install and configure TALL stack (Livewire, Alpine, Tailwind) |
| T0.3 | Configure .env: PostgreSQL, Redis, APP_KEY, session driver |
| T0.4 | Verify app runs: DB connection, Redis connection, welcome page |

---

## Dependencies

- T0.1 → T0.2 → T0.3 → T0.4
- T0.4 blocks T1.1.1, T2.1.1, T2.3.1, T6.1.1 (Phase 1 root tasks)
