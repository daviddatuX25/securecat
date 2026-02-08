# Phase 3 — Scoring + Controlled Result Release

> Close the loop from exam to outcomes: import scores, review, release results.

**Prerequisite:** Phase 2 (Intake Expansion + Documents) complete.

---

## 1. Outcome

At the end of Phase 3:

1. Admin can **import examination scores** from OMR CSV files with checksum-based integrity verification.
2. System **automatically maps scores to applicants** via applicant ID or seat number.
3. Admin can **manually enter or correct scores** with mandatory justification; all changes are logged with before/after values.
4. Admin reviews **score distributions and preliminary results** before releasing.
5. Admin performs a **controlled result release** — results become visible to examinees (or to the registrar for forwarding) only after explicit authorization.
6. Approved results generate a **downloadable PDF result slip**.
7. Every result view and download is **access-logged**.

---

## 2. Scope

### In Scope

| Area | Details |
|------|---------|
| **OMR Score Import** | Admin uploads OMR CSV. System calculates file checksum (SHA-256) and stores it. Validates column format, required fields, and data types. Maps rows to existing applicants via `applicant_id` or `exam_assignment.seat_number`. Preview with row-level validation (like Phase 2 CSV import). Commit writes scores to `Score` table. |
| **Checksum Verification** | File checksum stored at import time. Any subsequent access or re-import of the same file can verify integrity. Checksum mismatch triggers admin alert. |
| **Answer Key Management** | Admin uploads or enters answer key per exam session. Keys are stored encrypted at rest. Used for automatic score calculation if OMR provides raw answers instead of pre-calculated scores. |
| **Automatic Score Calculation** | If OMR provides raw answers, system compares against answer key and computes score. Supports simple scoring: correct = +1, incorrect = 0 (configurable). |
| **Manual Score Entry / Correction** | Admin can manually enter a score for an applicant (e.g., special cases). Admin can modify an imported score — system requires a text justification. Both logged with before/after in audit. |
| **Score Review Dashboard** | Admin views: score distribution histogram per session/course, pass/fail counts (if threshold defined), outlier detection (unusually high/low scores), individual score lookup. |
| **Result Release Workflow** | Admin marks a session/course as "ready for release" -> status changes to `pending_release`. Admin (or a second admin, if separation of duties is configured) performs "Release Results" action. Once released, results become viewable by examinees or by staff for forwarding. Release action is logged and irreversible (scores can still be corrected post-release, but corrections are separately logged). |
| **Examinee Result Access** | Minimal examinee-facing page: login with application reference number + date of birth (or email + password if account exists). View score and pass/fail status. Download PDF result slip. Each view/download logged. |
| **PDF Result Slip** | Generated server-side. Contains: applicant name, course, exam date, score, pass/fail, institution branding, timestamp of generation. Optionally includes a verification QR code (links to an online verification page). |
| **Score History** | Every score record maintains a history of changes (who, when, old value, new value, justification). |

### Non-Goals (Deferred)

- Offline proctor scanning (Phase 4)
- MFA, device registration (Phase 4)
- Hash-chained audit log (Phase 4)
- Advanced analytics / ML predictions (out of scope entirely)
- Automated course recommendation based on scores (out of scope)

---

## 3. User Journeys

### 3.1 Admin — Score Import

```
Admin logs in
  -> Navigates to "Scoring" -> selects exam session
  -> Clicks "Import OMR Scores"
  -> Uploads CSV file
  -> System computes SHA-256 checksum, stores it
  -> System validates:
      - Column format matches expected schema
      - Applicant IDs / seat numbers map to existing assignments
      - Score values within expected range
  -> Preview table:
      - Green: matched, valid score
      - Yellow: warning (e.g., score at extreme end)
      - Red: error (unknown applicant ID, out-of-range score)
  -> Admin reviews, commits
  -> Scores saved; confirmation: "38 scores imported, 2 unmatched"
```

### 3.2 Admin — Manual Score Correction

```
Admin searches for applicant "Juan Dela Cruz"
  -> Views current score: 78/100
  -> Clicks "Correct Score"
  -> Enters new score: 82/100
  -> Enters justification: "Rescoring found 4 additional correct answers on items 15, 23, 41, 56"
  -> Submits
  -> Audit log records: user, timestamp, score 78->82, justification
  -> Score history shows both entries
```

### 3.3 Admin — Result Release

```
Admin navigates to "Result Release" -> selects session/course
  -> Views score summary: distribution, pass/fail counts
  -> Clicks "Mark Ready for Release"
  -> Status changes to "pending_release"
  -> Admin (or second admin) clicks "Release Results"
  -> Confirmation dialog: "This will make results visible to 40 examinees. Proceed?"
  -> Confirms
  -> Results are now live
  -> Email notifications sent to examinees (if email available) or staff notified to inform applicants
```

### 3.4 Examinee — Result Viewing

```
Examinee navigates to result portal
  -> Enters application reference number + date of birth
  -> System checks: results released? -> Yes
  -> Displays: score, pass/fail, course, exam date
  -> Option: "Download Result Slip (PDF)"
  -> View and download both logged in audit
```

---

## 4. Data Model Changes

| Entity | Changes from Phase 2 |
|--------|---------------------|
| **ExamSession** | Add `scoring_status` enum (pending/imported/reviewed/released). Add `pass_threshold` (nullable integer). |
| **Score** (new) | `id`, `exam_assignment_id`, `raw_score`, `total_items`, `percentage`, `is_passing`, `imported_from_batch_id` (nullable), `created_at`, `updated_at`. |
| **ScoreHistory** (new) | `id`, `score_id`, `old_value`, `new_value`, `changed_by`, `justification`, `changed_at`. |
| **ScoreImportBatch** (new) | `id`, `exam_session_id`, `uploaded_by`, `file_name`, `file_checksum` (SHA-256), `row_count`, `success_count`, `error_count`, `status` (pending/committed/discarded), `created_at`. |
| **AnswerKey** (new) | `id`, `exam_session_id`, `data_encrypted` (JSON blob, encrypted at rest), `created_by`, `created_at`. |
| **ResultRelease** (new) | `id`, `exam_session_id`, `released_by`, `released_at`, `applicant_count`. Immutable once created. |
| **ResultAccess** (new) | `id`, `score_id`, `accessor_type` (examinee/staff/admin), `accessor_identifier`, `action` (view/download_pdf), `ip_address`, `accessed_at`. |

---

## 5. API / UI Surfaces

### New Pages

| Route Pattern | Role | Purpose |
|---------------|------|---------|
| `/admin/scoring/:session_id` | Admin | Score dashboard for a session |
| `/admin/scoring/:session_id/import` | Admin | OMR CSV upload + preview + commit |
| `/admin/scoring/:session_id/answer-key` | Admin | Manage answer key |
| `/admin/scoring/applicant/:id` | Admin | Individual score detail + correction form |
| `/admin/results/:session_id` | Admin | Result review + release controls |
| `/results` | Examinee | Result lookup (reference number + DOB) |
| `/results/slip/:token` | Examinee | Download PDF result slip |

### New API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/scoring/:session_id/import` | Upload OMR CSV, get validation preview |
| POST | `/api/scoring/:session_id/import/:batch_id/commit` | Commit score import |
| POST | `/api/scoring/:session_id/answer-key` | Upload/update answer key |
| GET | `/api/scoring/:session_id/summary` | Score distribution data |
| PATCH | `/api/scores/:id` | Manual score correction (requires justification) |
| GET | `/api/scores/:id/history` | Score change history |
| POST | `/api/results/:session_id/mark-ready` | Mark session ready for release |
| POST | `/api/results/:session_id/release` | Release results |
| POST | `/api/results/lookup` | Examinee result lookup |
| GET | `/api/results/slip/:token` | Generate/download PDF slip |

---

## 6. Security Controls (Phase 3 Additions)

| IAS Principle | Control | Implementation |
|---------------|---------|----------------|
| **Confidentiality** | Answer key encryption | Stored encrypted at rest; decrypted only in-memory during score calculation. Never exposed in API responses. |
| **Confidentiality** | Pre-release result hiding | Scores exist in DB but examinee-facing endpoints return 404 until `ResultRelease` record exists for the session. |
| **Confidentiality** | Result access logging | Every view and PDF download logged with accessor identity, IP, timestamp. |
| **Integrity** | File checksum verification | SHA-256 computed on upload; stored and compared if file is accessed later. Mismatch = alert. |
| **Integrity** | Score correction justification | Corrections require non-empty justification text. No silent edits. |
| **Integrity** | Import validation | Scores validated against expected ranges and applicant mappings before commit. |
| **Accountability** | Score history trail | Separate `ScoreHistory` table captures every change with full context. |
| **Accountability** | Release audit | `ResultRelease` record is immutable with releasing admin and timestamp. |
| **Non-Repudiation** | PDF verification QR | Optional QR on PDF result slip links to online verification, proving authenticity. |

---

## 7. Acceptance Criteria

### Demo Script

1. **OMR import:** Admin uploads OMR CSV for a session. Preview shows mapped scores. Commit succeeds. Scores visible in dashboard.
2. **Checksum integrity:** Re-upload same CSV — checksum matches. Modify one byte and re-upload — checksum mismatch alert.
3. **Manual correction:** Admin corrects a score. Justification required. Score history shows old/new.
4. **Score dashboard:** Admin views histogram, pass/fail counts, outlier highlights.
5. **Answer key:** Admin uploads answer key. System auto-calculates scores from raw answers (if applicable).
6. **Result release:** Admin marks ready -> releases. Verify status transitions. Verify release is logged.
7. **Examinee lookup:** Use reference number + DOB. See score and status. Download PDF.
8. **Pre-release block:** Before release, examinee lookup returns "results not yet available."
9. **Access log:** Admin checks result access log — sees examinee's view and download entries.

### Minimum Test Checklist

- [ ] OMR CSV: validates format, catches missing/invalid rows.
- [ ] OMR CSV: checksum computed and stored on commit.
- [ ] Score mapping: applicant IDs correctly matched.
- [ ] Score ranges: out-of-range values rejected.
- [ ] Manual entry: score saved, justification stored.
- [ ] Score history: all changes tracked with before/after.
- [ ] Answer key: encrypted at rest, usable for calculation.
- [ ] Result release: requires explicit admin action.
- [ ] Result release: irreversible (no un-release).
- [ ] Examinee access: blocked before release, allowed after.
- [ ] PDF generation: correct data, readable, includes verification QR (if enabled).
- [ ] Access logging: every view and download recorded.
- [ ] RBAC: only admin can import scores and release results.

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| OMR CSV format varies between OMR vendors | Import fails | Define a canonical CSV format; provide mapping configuration per vendor if needed. |
| Score correction abuse (admin changes scores without valid reason) | Integrity undermined | Justification required and logged; admin activity reports highlight frequent corrections. |
| Premature result access (data leak before release) | Trust loss | Results endpoint gated by `ResultRelease` record; no client-side hiding. |
| PDF generation performance under load | Slow downloads | Generate PDFs asynchronously on release; cache generated files. |
| Examinee authentication (reference + DOB is weak) | Unauthorized result access | Acceptable for Phase 3 given low-risk nature of viewing own result. Phase 4 can add stronger auth. |
