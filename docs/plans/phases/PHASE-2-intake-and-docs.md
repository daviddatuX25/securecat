# Phase 2 — Intake Expansion + Document Handling

> Make application intake scalable and auditable without breaking registrar ownership.

**Prerequisite:** Phase 1 (Core Operations) complete and deployed.

---

## 1. Outcome

At the end of Phase 2:

1. Registrar/staff can **bulk-import applicants via CSV** with a validation + review-before-commit screen.
2. An optional **REST API endpoint** accepts application data from external systems (e.g., registrar SIS) with the same review-before-commit pattern.
3. Staff and applicants (if encoding walk-in) can **upload supporting documents** (PDF, JPG, PNG) attached to applications.
4. Admin can **verify or request re-upload** of documents during the approval workflow.
5. **Notifications** (email and/or in-app) inform staff and admin of status changes.
6. Audit log now captures **before/after values** for every field change on applications and assignments.

---

## 2. Scope

### In Scope

| Area | Details |
|------|---------|
| **CSV Bulk Import** | Staff uploads a CSV file. System validates format, required fields, duplicates, and data types. Displays a preview table with row-level error/warning indicators. Staff reviews and confirms ("commit") or discards. Committed rows create applications in `pending_review`. |
| **API Intake Endpoint** | `POST /api/intake/applications` accepts JSON array of applicant+application objects. Returns validation result. Accepted records land in a "pending_review" staging area. Admin reviews via the same approval queue as manual and CSV entries. |
| **Document Upload** | File upload (PDF, JPG, PNG; max 10 MB per file). Linked to an application. Multiple documents per application. Virus scanning integration point (placeholder if ClamAV unavailable; log a warning). |
| **Document Verification Workflow** | During approval, admin sees uploaded documents alongside application. Actions: mark as verified, request re-upload (with note). |
| **Notifications** | Email notifications for: application approved, application rejected, revision requested, document re-upload needed. In-app notification badge/counter on admin and staff dashboards. |
| **Enhanced Audit Logging** | Audit records now include `before_value` and `after_value` (JSON) for update actions. Captures field-level diffs on applications, assignments, and user records. |
| **Intake Source Tracking** | Each application records its `intake_source` (manual / csv_import / api). Allows reporting on intake channel volume. |

### Non-Goals (Deferred)

- OMR score import (Phase 3)
- Result release (Phase 3)
- Offline proctor scanning (Phase 4)
- Hash-chained audit log (Phase 4)
- Examinee self-service application portal (future)

---

## 3. User Journeys

### 3.1 Staff — CSV Bulk Import

```
Staff logs in
  -> Navigates to "Bulk Import"
  -> Downloads CSV template (pre-filled headers)
  -> Uploads filled CSV
  -> System validates:
      - Required columns present
      - Data types correct (email format, date format, etc.)
      - No duplicate applicant entries (within file and against existing records)
      - Course codes match active courses in current period
  -> Preview table shown:
      - Green rows: valid, ready to import
      - Yellow rows: warnings (e.g., possible duplicate name)
      - Red rows: errors (missing required field, invalid course)
  -> Staff can fix issues in CSV and re-upload, or exclude red rows
  -> Staff clicks "Commit Import"
  -> System creates applicant + application records in "pending_review"
  -> Confirmation: "42 applications imported, 3 skipped (errors)"
```

### 3.2 Admin — Document Review During Approval

```
Admin opens approval queue (same as Phase 1, now enhanced)
  -> Application detail now shows "Documents" tab
  -> Admin views each uploaded document (inline preview or download)
  -> Admin marks each document as "Verified" or "Re-upload Needed" (with note)
  -> If any document needs re-upload, application cannot be approved until resolved
  -> Once all documents verified, admin can approve as before
```

### 3.3 Staff — Responding to Re-upload Request

```
Staff sees notification: "Application #1234 requires document re-upload"
  -> Opens application
  -> Sees admin note: "Transcript is blurry, please re-scan"
  -> Uploads replacement document
  -> Application returns to "pending_review" state
```

### 3.4 External System — API Intake

```
Registrar SIS sends POST /api/intake/applications
  with JSON body: [{applicant_data, course_code, documents_base64?}, ...]
  -> System validates and returns:
      { accepted: 38, rejected: 2, errors: [{row: 5, field: "email", msg: "invalid"}] }
  -> Accepted records appear in admin approval queue with intake_source = "api"
  -> Admin reviews and approves/rejects as normal
```

---

## 4. Data Model Changes

| Entity | Changes from Phase 1 |
|--------|---------------------|
| **Application** | Add `intake_source` enum (manual / csv_import / api). |
| **Document** (new) | `id`, `application_id`, `file_name`, `file_path`, `file_size`, `mime_type`, `uploaded_by`, `uploaded_at`, `scan_status` (pending/clean/infected), `verification_status` (pending/verified/reupload_requested), `admin_notes`. |
| **ImportBatch** (new) | `id`, `uploaded_by`, `file_name`, `row_count`, `success_count`, `error_count`, `status` (pending/committed/discarded), `created_at`. |
| **ImportBatchRow** (new) | `id`, `import_batch_id`, `row_number`, `raw_data` (JSON), `validation_status` (valid/warning/error), `validation_messages` (JSON), `application_id` (null until committed). |
| **Notification** (new) | `id`, `user_id`, `type`, `title`, `message`, `entity_type`, `entity_id`, `is_read`, `created_at`. |
| **AuditLog** | Add `before_value` (JSON, nullable), `after_value` (JSON, nullable). |

---

## 5. API / UI Surfaces

### New Pages

| Route Pattern | Role | Purpose |
|---------------|------|---------|
| `/staff/import` | Staff | CSV upload, validation preview, commit |
| `/staff/import/template` | Staff | Download CSV template |
| `/staff/applications/:id/documents` | Staff | Upload / replace documents |
| `/admin/applications/:id/documents` | Admin | Review / verify documents |
| `/notifications` | All | In-app notification list |

### New / Modified API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/intake/csv` | Upload CSV, get validation preview |
| POST | `/api/intake/csv/:batch_id/commit` | Commit validated batch |
| DELETE | `/api/intake/csv/:batch_id` | Discard batch |
| GET | `/api/intake/csv/template` | Download CSV template |
| POST | `/api/intake/applications` | API intake (JSON array) |
| POST | `/api/documents` | Upload document for an application |
| GET | `/api/documents/:id` | Download/view document |
| PATCH | `/api/documents/:id/verify` | Admin marks verified |
| PATCH | `/api/documents/:id/request-reupload` | Admin requests re-upload |
| GET | `/api/notifications` | List notifications for current user |
| PATCH | `/api/notifications/:id/read` | Mark notification as read |

---

## 6. Security Controls (Phase 2 Additions)

| IAS Principle | Control | Implementation |
|---------------|---------|----------------|
| **Confidentiality** | Document access control | Only the uploading staff, assigned admin, and system can access a document file. Direct URL guessing prevented by non-sequential IDs or signed URLs. |
| **Integrity** | CSV validation | Server-side validation before commit; no raw CSV data written to core tables until reviewed. |
| **Integrity** | File type validation | MIME type + extension checked server-side. Only PDF/JPG/PNG accepted. |
| **Integrity** | Virus scanning | Uploaded files passed through ClamAV (or placeholder log) before storage. Infected files rejected. |
| **Integrity** | API authentication | API intake endpoint requires a pre-shared API key or token (issued by admin). Rate-limited. |
| **Accountability** | Before/after audit diffs | Every update now records what changed (field-level JSON diff). |
| **Accountability** | Intake source tracking | Every application records whether it came from manual, CSV, or API — fully traceable. |

---

## 7. Acceptance Criteria

### Demo Script

1. **CSV import (happy path):** Staff downloads template, fills 10 rows, uploads. Preview shows 10 green rows. Staff commits. 10 applications appear in admin queue with `intake_source = csv_import`.
2. **CSV import (errors):** Upload CSV with 2 invalid rows (missing name, bad email). Preview shows 8 green, 2 red. Staff commits — 8 imported, 2 skipped.
3. **API intake:** Send POST with 5 valid applications. Verify 5 appear in queue with `intake_source = api`. Send POST with invalid data — verify error response.
4. **Document upload:** Staff uploads PDF and JPG for an application. Admin opens application — sees documents. Admin verifies one, requests re-upload on other.
5. **Re-upload flow:** Staff sees notification. Uploads replacement. Admin re-reviews and verifies.
6. **Approval with documents:** Admin cannot approve until all documents are verified. After verification, approval proceeds as Phase 1.
7. **Notifications:** Verify email sent on approval. Verify in-app badge increments for staff on revision request.
8. **Audit diffs:** Admin changes an application note. Audit log shows before/after values.

### Minimum Test Checklist

- [ ] CSV: template download works.
- [ ] CSV: validation catches missing required fields, bad types, duplicates.
- [ ] CSV: commit creates correct applicant + application records.
- [ ] CSV: discard cleans up batch without side effects.
- [ ] API: valid payload creates applications.
- [ ] API: invalid payload returns structured errors, no partial writes.
- [ ] API: unauthenticated requests rejected (401).
- [ ] Document upload: file size limit enforced (10 MB).
- [ ] Document upload: only PDF/JPG/PNG accepted.
- [ ] Document: virus scan integration point called.
- [ ] Document: access control — other staff cannot see documents they didn't upload (only admin can).
- [ ] Notifications: email delivery (or log in dev mode).
- [ ] Audit: before/after values present on update actions.

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Large CSV files (1000+ rows) slow to validate | Poor UX, timeouts | Process validation asynchronously; show progress bar; set reasonable row limit (e.g., 500 per batch). |
| Virus scanning unavailable in dev/staging | Infected files slip through | Placeholder mode logs warning but allows upload; production must have ClamAV active. |
| API endpoint abused (spam applications) | Queue flooded | Rate limiting + API key authentication. Admin can disable API intake per period. |
| Email delivery failures (SMTP issues) | Staff/admin miss notifications | In-app notifications serve as fallback. Email failures logged for admin review. |
| Registrar CSV format changes over time | Import breaks | Template download ensures correct format. Validation errors clearly indicate which columns are wrong. |
