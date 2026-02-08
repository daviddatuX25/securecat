# SecureCAT — Quality Gates

> Phase 1 required tests, security checks, logging/monitoring minimums, and release checklist for a working product.

**Reference:** [MASTER.md](MASTER.md) §5 Definition of Done, [PHASE-1-scope-freeze.md](phases/PHASE-1-scope-freeze.md), [PHASE-1-core-ops.md](phases/PHASE-1-core-ops.md) §7.

---

## 1. Phase 1 Test Checklist

### Unit / Component Tests

| Area | What to test |
|------|----------------|
| **Auth** | Password hash verify; session create/lookup/expiry; role resolution from session. |
| **RBAC** | Permission denied for Staff on admin-only action; denied for Proctor on staff encode; Admin allowed. |
| **Application state machine** | Transitions: draft → pending_review; pending_review → approved | rejected | revision_requested; revision_requested → pending_review. Invalid transitions rejected. |
| **Scheduling** | Room capacity: assignment fails when session room is at capacity; success when under capacity. |
| **QR** | Same payload + secret produces same signature; changed payload or wrong secret → verification fails. |
| **QR validation** | Valid payload + correct session + within time → valid; wrong session → invalid with reason; duplicate scan → invalid with reason. |
| **Input validation** | Required fields missing → 400; invalid email/date/format → 400; error response includes field-level messages. |
| **Audit logger** | Each mutating action (create period, create application, approve, scan) produces one audit record with user_id, role, action, entity_type, entity_id, timestamp, ip_address. |

### Integration Tests (API + DB)

| Scenario | Steps | Assertions |
|----------|--------|------------|
| **Login → encode → approve → assign** | Login as staff; POST applicant; login as admin; GET applications; POST approve with exam_session_id | Application approved; ExamAssignment exists; qr_payload and qr_signature present. |
| **Scan flow** | POST /api/scan with valid payload/signature (proctor session) | 200, result=valid; ScanEntry created with validation_result=valid. |
| **Scan duplicate** | POST /api/scan same assignment again | 200, result=invalid, failure_reason indicates duplicate; second ScanEntry with validation_result=invalid. |
| **Scan wrong session** | POST /api/scan with payload for session A, proctor assigned to session B | 403 or 200 with result=invalid, reason wrong session. |
| **CSRF** | State-changing request without valid CSRF token | 403. |
| **RBAC** | GET /api/audit-log as Staff | 403. |

### E2E / Manual Smoke (Phase 1 Demo Script)

Execute [PHASE-1-core-ops.md](phases/PHASE-1-core-ops.md) §7 Demo Script end-to-end:

1. Admin: create period → course → room → session → assign proctor.
2. Staff: encode 3 applicants; verify in admin approval queue.
3. Admin: approve 2, reject 1 with note; assign approved to session; verify capacity respected.
4. Print admission slip; verify QR visible and correct.
5. Proctor: scan valid QR → green, name shown.
6. Proctor: scan wrong-session QR → red, “wrong session” (or equivalent).
7. Proctor: scan same QR again → “already scanned.”
8. View roster and attendance; data correct.
9. Admin: open audit log; entries for create period, encode, approve, assign, scan.
10. Staff tries `/admin/audit-log` → 403; Proctor tries `/staff/encode` → 403.

### UI testing and visual review

- **UI testing:** For UI work, supplement automated tests with manual browser testing at checkpoints; see `.cursor/rules/phase-delivery-and-agent-practices.mdc`.
- **Real seeders:** For local/demo and visual review, run real seeders (e.g. `php artisan db:seed`) so the app has realistic data; not required in CI on every run.
- **Visual review:** Checkpoints (e.g. after UI tasks, before phase demo) are documented in phase-delivery rules; ensure there is a clear point where visual review happens.
- **Code review:** At phase completion and when many modules are touched, do a code review (quality, fit, security); see release checklist and phase-delivery rules.

---

## 2. Phase 1 Security Checks

| Check | How |
|-------|-----|
| **Authorization (RBAC)** | Every protected endpoint returns 403 when called with a role that is not allowed. Automated: call admin-only endpoints as Staff/Proctor; staff-only as Proctor/Admin; proctor-only as Admin/Staff. |
| **Audit immutability** | Phase 1: audit table is append-only in practice (no update/delete in application code). Review: no code path updates or deletes audit records. (Phase 4 adds DB-level enforcement.) |
| **QR signature verification** | Unit test: tampered payload (e.g. change applicant_id or session_id) fails verification. Integration: scan with modified payload returns invalid + reason (e.g. “Invalid or tampered QR”). |
| **No secrets in code** | Grep / secret scan: no hardcoded passwords, API keys, or QR signing secret; all from environment/config. |
| **HTTPS only** | Env and deployment: TLS enabled; HTTP redirects to HTTPS; no mixed content in UI. |
| **Input validation** | All API inputs validated server-side (type, length, format, allowed values). No raw input used in SQL or redirects without validation. |

---

## 3. Logging & Monitoring Minimums (Phase 1)

| Area | Minimum |
|------|---------|
| **Application log** | Errors and warnings (with context: route, user_id, request_id if available). Startup and config errors. |
| **Security events** | Login failures (no password in log); 403 events (optional, may be high volume). |
| **Audit log** | As per SC-10: every mutation produces an audit record (user, role, action, entity, timestamp, IP). Stored in audit_log table; no requirement for separate SIEM in Phase 1. |
| **Performance** | No strict SLA in Phase 1; optional: log slow requests (e.g. > 2s) for review. |

---

## 4. Release Checklist — “Working Product” (Phase 1)

Before marking Phase 1 complete:

- [ ] **Functional**  
  - [ ] All steps of the [Phase 1 demo flow](phases/PHASE-1-scope-freeze.md#1-single-end-to-end-demo-flow-thin-vertical-slice) are demonstrable.  
  - [ ] Acceptance criteria in [PHASE-1-core-ops.md](phases/PHASE-1-core-ops.md) §7 pass.  
  - [ ] No critical or high-severity bugs open for Phase 1 scope.

- [ ] **Security**  
  - [ ] RBAC enforced on every endpoint and UI route.  
  - [ ] Audit log record for every mutating action in scope.  
  - [ ] No secrets in code; config via environment.  
  - [ ] HTTPS enforced; no mixed content.  
  - [ ] Input validation on all user-supplied data.  
  - [ ] Phase 1 security checks above executed and passed.

- [ ] **Quality**  
  - [ ] Core business logic covered by automated tests (unit or integration).  
  - [ ] Manual smoke (demo script) executed successfully.  
  - [ ] Code reviewed by at least one other team member.

- [ ] **Documentation**  
  - [ ] Deployment/runbook: how to start app, DB, Redis; env vars; how to run migrations.  
  - [ ] Phase 1 scope and DoD referenced (this doc + PHASE-1-scope-freeze + MASTER §5).
