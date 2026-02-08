# QR: No-Regenerate on Session Edit — Refactor Plan

> **Status: IMPLEMENTED.** QR payload is now minimal (`applicant_id` + `exam_session_id` + `generated_at`). Scan validation uses the session record (DB) as source of truth for date/time/room. Printed slips stay valid after admin edits session details. Old-format QR codes (with room_id/schedule) are backward compatible.

> **Goal:** When admin edits an exam session (date, time, room), **do not** regenerate QR payloads. Existing printed admission slips stay valid. Scan validation uses the **session record** (current date/time/room) as source of truth so security remains strong.

**References:** [PHASE-1-BOUNDARY-AND-DEMO.md](PHASE-1-BOUNDARY-AND-DEMO.md) §5, SecureCAT-ulc (reversed), scan flow in [ScanController](../app/app/Http/Controllers/Api/ScanController.php).

---

## 1. Current behaviour (post-refactor)

| Aspect | Behaviour |
|--------|-----------|
| QR payload | Minimal: `applicant_id`, `exam_session_id`, `generated_at` only. No room_id or schedule. |
| On PATCH exam-sessions/:id | Session row only is updated. Assignment QR payloads are **not** changed. |
| Printed slips after session edit | Still valid. No reprint required. |
| Scan "time window" | Uses **session** date/start/end from DB (ScanController step 6). |
| Scan "room" check | No room check — session is source of truth. Old-format QR codes (with room_id/schedule) are accepted; extra fields are ignored. |

---

## 2. Implementation steps (completed)

### Step 1: QR payload simplified ✓

- **File:** `app/app/Services/QrSigningService.php`
- **Change:** `buildPayload()` now takes only `applicantId` and `examSessionId`. Removed `roomId`, `date`, `startTime`, `endTime` params. Payload is `{applicant_id, exam_session_id, generated_at}`.

### Step 2: ScanController — minimal payload, server-side validation ✓

- **File:** `app/app/Http/Controllers/Api/ScanController.php`
- **Change:** Only `applicant_id` and `exam_session_id` are extracted from QR payload. Room, schedule, etc. are looked up from the session DB record. Old-format QR codes (with extra fields) still work — extra fields are simply ignored. Fixed latent bug where `invalidResponse()` method was called but didn't exist.

### Step 3: Callers updated ✓

- **File:** `app/app/Http/Controllers/Api/ApplicationController.php` — `createAssignmentWithCapacityCheck()` calls simplified `buildPayload(applicantId, sessionId)`.
- **File:** `app/database/seeders/DatabaseSeeder.php` — QR generation uses new minimal `buildPayload()`.

### Step 4: Tests updated ✓

- **ScanApiTest:** All tests use minimal QR payloads. Key new test: `test_session_edited_after_qr_generated_still_valid` — verifies that changing session room/date/time after QR generation doesn't invalidate the scan.
- **ExamSessionApiTest:** `test_update_does_not_regenerate_qr_assignments_stay_valid` uses minimal payload format.

---

## 3. Security summary (unchanged)

- **HMAC:** Scan still verifies signature of the submitted payload; forgeries and tampering are rejected.
- **Binding:** Assignment is resolved by applicant_id + exam_session_id; proctor must be assigned to that session.
- **Time window:** Uses **session's** current date and start/end time from DB.
- **Duplicate:** One valid scan per assignment.
- **Audit:** Scan attempts (valid/invalid) still logged.

QR payload contains only immutable identifiers. All mutable data (room, date, time) is looked up from the session DB record at scan time.
