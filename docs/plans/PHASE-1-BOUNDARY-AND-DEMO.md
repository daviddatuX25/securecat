# Phase 1 — Frozen Boundary & Demo / Manual Test Guide

> Use this doc to know **what is Phase 1 scope** (fix now) vs **later phases** (suggest or backlog), and to run the **demo script** plus **manual test cases** for sign-off.

**References:** [PHASE-1-scope-freeze.md](phases/PHASE-1-scope-freeze.md), [PHASE-1-core-ops.md](phases/PHASE-1-core-ops.md), [QUALITY-GATES.md](QUALITY-GATES.md).

---

## 1. Phase 1 frozen boundary (when to fix now vs suggest later)

**Fix or improve now (Phase 1 in scope):**

- Anything in the [Phase 1 demo flow](phases/PHASE-1-scope-freeze.md#1-single-end-to-end-demo-flow-thin-vertical-slice) that does not work or is confusing.
- Bugs in: auth, RBAC, admission periods, courses, rooms, exam sessions, applicant encoding, approval/reject/revision, **revision → staff can edit and resubmit**, assign to session (including “Assign to session” for already-approved apps), QR/admission slip, scan (valid/duplicate/wrong session), roster/attendance, audit log.
- UI/UX issues that block the demo: wrong labels, forms not hydrating (e.g. session edit course/room), missing “Assign to session” on application detail, “Back to applications” vs “Approval queue” wording.
- Security: RBAC on every route, audit for every mutation, input validation, no secrets in code.

**Suggest or backlog (out of Phase 1 — do not implement now):**

| Area | Phase 1 boundary | Later phase |
|------|------------------|-------------|
| Intake | Manual encoding only | Phase 2: CSV/API bulk import |
| Documents | No document upload/verification | Phase 2 |
| Notifications | No email/in-app notifications | Phase 2 |
| Scoring | No OMR, no score import/release | Phase 3 |
| Result slips | No PDF result release | Phase 3 |
| Offline scan | No offline proctor scanning | Phase 4 |
| MFA | No MFA for admins | Phase 4 |
| Audit | No hash-chained / append-only DB enforcement | Phase 4 |
| Examinee portal | No examinee login/self-service | Future |

**Rule of thumb:** If it’s in [PHASE-1-scope-freeze.md §2 In scope](phases/PHASE-1-scope-freeze.md#2-in-scope-vs-out-of-scope) or required for the demo flow, **fix or polish now**. If it’s in “Out of scope” or a later phase, **suggest or file a bd issue** for the right phase.

---

## 2. Demo script (end-to-end)

Run with a seeded DB: `docker exec -it <app-container> php artisan db:seed` (login: **admin@example.com** / **password**).

1. **Admin setup**  
   Create period → course → room → exam session → assign proctor. Confirm they appear in the admin dashboard / lists.

2. **Staff encodes**  
   Log in as staff, encode 3 applicants (same course). Confirm they appear in admin’s Applications list with status “Pending review”.

3. **Admin approves**  
   Open Applications, filter by “Pending review”. Open one application: Approve (optionally assign to session); approve another with “Assign to session”; reject one with a note. Confirm state transitions and that capacity is respected when assigning.

4. **Revision flow**  
   For one “Pending review” application, click “Request revision” with a note. Log in as staff, open “My applications”, filter “Revision requested”. Open that application, click **Revise**, edit fields, submit. Confirm status returns to “Pending review” and admin can see it again.

5. **Assign already-approved**  
   Approve an application without assigning. On the application detail page, confirm an **“Assign to session”** button appears; use it to assign. Confirm assignment and “Print admission slip” appear.

6. **Print slip**  
   Open the printable admission slip for an assigned applicant. Confirm QR is visible and data matches.

7. **Proctor scan (valid)**  
   Log in as proctor, open assigned session, scan the valid QR (or use manual text input). Confirm green result and applicant name.  
   *Note:* For scan to pass “within time window”, the session date/time must include “now”. Camera-based auto-scan is deferred to Phase 2; manual QR text input works in Phase 1.

8. **Proctor scan (wrong session / duplicate)**  
   Scan a QR for another session (or same QR again). Confirm red result with “Wrong session” or “Already scanned”.  
   *Note:* If session dates are in the past, create a session with today’s date and assign an applicant to test valid/duplicate scan.

9. **Reports**  
   View roster and attendance for the session. Confirm assigned list and scanned vs not-scanned. Exam sessions list shows **Capacity** (e.g. 2 / 40) and a **Roster** link when there are assignments.

10. **Audit log**  
    As admin, open audit log. Confirm entries for period create, encode, approve, assign, scan.

11. **RBAC**  
    As staff, open `/admin/audit-log` → 403 or redirect. As proctor, open `/staff/encode` → 403 or redirect.

---

## 3. Extra manual test cases (optional)

Use these to harden Phase 1 before sign-off:

- **Applications list**  
  Admin: filter by Approved, Rejected, Revision requested. Confirm list and “View” work. Back link from application detail says “Applications” (not only “Approval queue”).

- **Capacity**  
  Create a room with capacity 1, create a session in it. Assign one applicant; try to assign a second to the same session. Confirm validation error (room at capacity).

- **Validation**  
  Staff encode: submit with missing required fields, invalid email, or invalid date. Confirm 422 and field-level error messages.

- **Revise only own**  
  Staff A encodes an application; admin requests revision. Staff B (different user) must not be able to revise it (403 or “not allowed” in UI). Manually test: log in as another staff, open the revise URL or try PATCH; expect 403.

- **Session edit**  
  As admin, edit an exam session: change date, time, or course/room/proctor. Save. Re-open the session; confirm changes persisted. Course, Room, and Proctor dropdowns show current values (not placeholders).

- **Assign only when approved**  
  Admin: on a “Pending review” application, confirm there is no “Assign to session” button (only Approve/Reject/Request revision). After approve without assign, confirm “Assign to session” appears.

---

## 4. Checklist before marking Phase 1 complete

- [ ] Demo script (§2) runs without blocking issues.
- [ ] Revision flow: staff can revise and resubmit; admin sees back in queue.
- [ ] Admin application detail: “← Applications” back link; “Assign to session” when approved and not yet assigned.
- [ ] Session edit: course and room dropdowns show current values.
- [ ] Automated tests and quality gates per [QUALITY-GATES.md](QUALITY-GATES.md) pass.
- [ ] Phase 1 boundary (§1) used to decide “fix now” vs “suggest / backlog”.

---

## 5. Cases to study / deferred

**Session edit vs existing QR (no-regenerate policy)**  
- When an admin edits an exam session (e.g. date or time), we do **not** regenerate QR codes for existing assignments. The QR payload stored in `exam_assignments` still contains the original schedule. Scan validation uses the **session’s current** date/time window and proctor assignment; the payload’s `applicant_id`, `exam_session_id`, and signature are still verified. So: changing the session time does not invalidate the QR signature; it may change whether a scan is “within time window”. Document this behaviour and ensure no security gap (e.g. scan still requires correct session and proctor). See bd issue for “Session edit vs QR payload” if needed.

Implementation: session edit does not regenerate QR; scan uses session (DB) for time window and does not reject on payload room mismatch. See [QR-NO-REGENERATE-REFACTOR.md](QR-NO-REGENERATE-REFACTOR.md). Printed slips stay valid; no reprint when session is edited.

**Camera-based QR scan**  
- Phase 1: manual text input for QR works. Camera auto-scan is **deferred to Phase 2**. Track in bd.

**Admin performing staff/proctor actions**  
- Phase 1 RBAC: only staff can encode applicants; only proctor can scan. Allowing admin to also encode (or perform other staff/proctor actions) is a **deep dive**: RBAC matrix, UI entry points, and consistency. Track in bd for design.

**Frontend components and templates**  
- Reusing modals and UI (prefixed/templated) for consistency: plan a **component/template strategy** (e.g. shared Alpine components or Blade partials) so future work can reuse patterns. Track in bd.
