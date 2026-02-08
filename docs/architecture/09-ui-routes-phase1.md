# 09 — UI Routes (Phase 1)

> Route map, role guards, page responsibilities, and minimal UI components for Phase 1.

**Reference:** [PHASE-1-core-ops.md](../plans/phases/PHASE-1-core-ops.md) §5, [PHASE-1-scope-freeze.md](../plans/phases/PHASE-1-scope-freeze.md).

---

## 1. Route Map

| Route | Role guard | Purpose |
|-------|------------|---------|
| `/login` | None (redirect authenticated to role home) | Login form |
| `/` | Authenticated | Redirect to role-specific home |
| `/admin/dashboard` | Admin | Dashboard: pending count, upcoming sessions |
| `/admin/periods` | Admin | List admission periods |
| `/admin/periods/new` | Admin | Create admission period |
| `/admin/periods/:id/edit` | Admin | Edit admission period |
| `/admin/courses` | Admin | List courses (filter by period) |
| `/admin/courses/new` | Admin | Create course |
| `/admin/courses/:id/edit` | Admin | Edit course |
| `/admin/rooms` | Admin | List rooms |
| `/admin/rooms/new` | Admin | Create room |
| `/admin/rooms/:id/edit` | Admin | Edit room |
| `/admin/sessions` | Admin | List exam sessions |
| `/admin/sessions/new` | Admin | Create exam session |
| `/admin/sessions/:id/edit` | Admin | Edit exam session (assign proctor) |
| `/admin/applications` | Admin | Approval queue (list + filters) |
| `/admin/applications/:id` | Admin | Application detail + approve/reject/revision + assign |
| `/admin/assignments` | Admin | List exam assignments (optional; or via application detail) |
| `/admin/audit-log` | Admin | Browse audit log with filters |
| `/admin/reports/roster` | Admin | Select session → roster |
| `/admin/reports/attendance` | Admin | Select session → attendance |
| `/staff/encode` | Staff | Encode applicant form |
| `/staff/applications` | Staff | List applications encoded by me |
| `/staff/applications/:id` | Staff | View application (read-only) |
| `/proctor/sessions` | Proctor | List my assigned sessions |
| `/proctor/scan/:session_id` | Proctor | QR scan page for session |
| `/proctor/attendance/:session_id` | Proctor | Live attendance for session |
| `/print/admission-slip/:assignment_id` | Admin, Staff | Printable admission slip with QR |

---

## 2. Role Guards

- **Admin:** Can access all `/admin/*` and `/print/admission-slip/*`.
- **Staff (Registrar):** Can access `/staff/*` and `/print/admission-slip/*`. Any attempt to access `/admin/*` (except print) or `/proctor/*` → 403 or redirect to `/staff/applications` (or login).
- **Proctor:** Can access `/proctor/*` only. Any attempt to access `/admin/*` or `/staff/*` → 403 or redirect to `/proctor/sessions`.
- **Unauthenticated:** Only `/login` allowed; else redirect to `/login`.
- **Authenticated:** Visiting `/` redirects to role home: Admin → `/admin/dashboard`, Staff → `/staff/applications`, Proctor → `/proctor/sessions`.

---

## 3. Page Responsibilities

| Page | Responsibility |
|------|----------------|
| **Login** | Email + password; submit → POST /api/auth/login; on success set session and redirect by role; show validation/401 errors. |
| **Admin dashboard** | Fetch pending count + upcoming sessions; links to approval queue, sessions, reports. |
| **Admin periods/courses/rooms/sessions** | List (with optional filters); create/edit forms with validation; delete with confirmation; all mutations via API with CSRF. |
| **Admin applications** | List pending_review (and optionally other statuses); link to detail. |
| **Admin application detail** | Show applicant + application + course; buttons Approve / Reject / Request Revision; on Approve, show “Assign to session” (session dropdown, capacity hint, seat optional); after assign, link to print admission slip. |
| **Admin audit log** | Table with user, action, entity, timestamp, IP; filters (user, action, date range); pagination. |
| **Admin reports** | Roster: session selector → table of assigned applicants. Attendance: session selector → table of assigned with scanned yes/no and time. |
| **Staff encode** | Single form: applicant fields + three preferred course dropdowns (first required, second and third optional; distinct); submit → POST /api/applicants; success → message + link to application or encode another. |
| **Staff applications** | List applications encoded by current user; link to read-only detail. |
| **Proctor sessions** | List assigned sessions (date, course, room); link to scan and attendance per session. |
| **Proctor scan** | Session context; camera or manual QR input; submit → POST /api/scan; show result (valid: name + success; invalid: reason). Show live count of scanned for session. |
| **Proctor attendance** | Read-only list of assignments for session with scanned status. |
| **Print admission slip** | Fetch assignment + QR; render printable page (QR image, exam details, applicant name). No edit; optional “Print” button. |

---

## 4. Empty / Loading / Error States

| Context | Empty | Loading | Error |
|---------|--------|---------|--------|
| **Lists** (periods, courses, rooms, sessions, applications) | “No items yet. Create one.” + CTA where applicable | Skeleton or spinner | “Could not load. Retry.” + retry button |
| **Approval queue** | “No pending applications.” | As above | As above |
| **Application detail** | N/A (id required) | Spinner | “Application not found” or 403 message |
| **Proctor sessions** | “No sessions assigned to you.” | Spinner | “Could not load sessions.” |
| **Scan result** | N/A | After submit: “Validating…” | Show invalid reason; do not clear form so user can rescan |
| **Roster / Attendance** | “No assignments for this session.” | Spinner after session select | “Failed to load.” |
| **Audit log** | “No records match filters.” | Spinner | “Could not load audit log.” |
| **Global** | — | — | 403: “You don’t have access”; 401: redirect to login; 5xx: “Something went wrong. Try again.” |

---

## 5. Minimal UI Components List

- **Layout:** App shell with role-based navigation (sidebar or top nav); logout; breadcrumbs where helpful.
- **Auth:** Login form; session expiry handling (redirect to login or modal).
- **Forms:** Input (text, email, number, date, time), textarea, select (dropdown), submit button; client-side validation mirroring API rules; display API validation errors by field.
- **Tables:** Sortable/filterable where specified (e.g. audit log); pagination for list endpoints.
- **Buttons:** Primary/secondary; destructive (reject, delete) with confirmation modal.
- **Feedback:** Inline success/error messages; toasts or banners for global actions.
- **Proctor scan:** Camera capture or file/input for QR; result panel (success vs failure with reason).
- **Print:** Admission slip page optimized for print (QR, exam details, applicant name); no nav/footer in print view if possible.
- **Role guard:** Wrapper or middleware that checks role and redirects or shows 403 for disallowed routes.
