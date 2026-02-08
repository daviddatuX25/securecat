# Skeleton Frontend Plan — Test Backend

> Minimal UI to verify auth, RBAC, and API endpoints work before building full Phase 1 flows.

**Purpose:** Prove backend (login, logout, dashboard, RBAC) works end-to-end via a simple browser UI.

**Stack:** Laravel Blade + Vite + Tailwind + Alpine.js (existing TALL setup). No Livewire needed for the skeleton.

---

## 1. What We're Testing

| Backend Piece | How to Test |
|---------------|-------------|
| POST /api/auth/login | Login form → success stores session, redirects |
| POST /api/auth/logout | Logout button → 204, clears session |
| GET /api/dashboard | Admin sees counts; Staff/Proctor get 403 |
| RBAC middleware | Staff can't reach /admin/dashboard; Admin can |
| Session cookie + CSRF | Browser sends cookie; POST works with CSRF token |

---

## 2. Skeleton Scope (Minimal)

### 2.1 Routes

| Route | Auth | Purpose |
|-------|------|---------|
| `/login` | None | Login form (email + password) |
| `/` | Any | Redirect: guest → `/login`; auth → role home |
| `/admin/dashboard` | Admin | Stub page that fetches GET /api/dashboard |
| `/staff/home` | Staff | Stub "Staff home" (no API yet) |
| `/proctor/home` | Proctor | Stub "Proctor home" (no API yet) |

### 2.2 Pages

- **Login:** Form with email, password; submit → `POST /api/auth/login` (JSON). On 200: store user in session (or rely on Laravel session cookie), redirect by role. On 401: show "Invalid credentials".
- **Admin Dashboard:** Fetches `GET /api/dashboard`, shows `pending_applications_count` and `upcoming_sessions`. Logout button.
- **Staff/Proctor Home:** Simple "Hello, {name}" + Logout. Proves RBAC: Staff cannot visit `/admin/dashboard` (403 or redirect).

### 2.3 Layout

- Shared layout: header with user name, role badge, Logout.
- Logout: `POST /api/auth/logout` with CSRF token; on 204 redirect to `/login`.

---

## 3. Implementation Steps

### Step 1 — Web routes and auth check

1. Add routes: `/login` (GET), `/` (GET), `/admin/dashboard`, `/staff/home`, `/proctor/home`.
2. Middleware: `guest` for `/login`; `auth` + `rbac` for the rest.
3. Web routes need RBAC: ensure `config/rbac.php` has `GET admin/*`, `GET staff/*`, `GET proctor/*` (already present per conversation summary).
4. Controller or inline closures for now.

### Step 2 — Login page

1. Create `resources/views/auth/login.blade.php`.
2. Form: `action="/api/auth/login"` with `method="POST"` and `@csrf` — but API login expects JSON. Options:
   - **A:** Use a small form that submits via `fetch()` to `/api/auth/login` (JSON), then redirect on success. No full page reload; need JS.
   - **B:** Create a web login route that accepts form POST and proxies to AuthController or validates then `Auth::login()` server-side, then redirect. Simpler, no JS for submit.
3. Recommended: **B** — Add `POST /login` web route that accepts `email`/`password`, validates, calls `Auth::login()`, redirects. Reuse AuthController logic or a thin web LoginController that uses the same validation + `Auth::login()`. This keeps CSRF and session cookie flow native.

### Step 3 — Role-based redirect

1. After login (or when visiting `/` while authenticated): redirect by `user->role`:
   - admin → `/admin/dashboard`
   - staff → `/staff/home`
   - proctor → `/proctor/home`

### Step 4 — Dashboard page (Admin)

1. Create `resources/views/admin/dashboard.blade.php`.
2. Page loads; use Alpine.js or a small script to `fetch('/api/dashboard', { credentials: 'include' })` and render `pending_applications_count`, `upcoming_sessions`.
3. Or: pass data from controller via `view(..., ['data' => ...])` — controller calls API internally or reads from DB. For skeleton, fetching client-side is fine and tests the API directly.

### Step 5 — Logout

1. Logout form/button: `POST /api/auth/logout` with `@csrf` and `credentials: 'include'`.
2. Or add `POST /logout` web route that calls `Auth::logout()` and redirects to `/login`. Either way, session must be cleared.

### Step 6 — Layout and RBAC verification

1. Create `resources/views/layouts/app.blade.php`: header, `{{ $slot }}` or `@yield('content')`, user name, Logout.
2. Wrap admin/staff/proctor routes with `rbac` middleware. Verify: log in as Staff, try to open `/admin/dashboard` → 403 or redirect to staff home.
3. Create test users: admin (test@example.com or admin@example.edu), staff, proctor — via seeder.

---

## 4. Test Users (Seeder)

Ensure `DatabaseSeeder` or a dedicated seeder creates:

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | admin |
| staff@example.com | password | staff |
| proctor@example.com | password | proctor |

(Use `User::factory()->admin()->create([...])` etc. with known emails for manual testing.)

---

## 5. File Checklist

| File | Purpose |
|------|---------|
| `routes/web.php` | Login, home, admin/staff/proctor routes |
| `app/Http/Controllers/Auth/WebLoginController.php` | (optional) Web login handler |
| `resources/views/auth/login.blade.php` | Login form |
| `resources/views/layouts/app.blade.php` | Shared layout, logout |
| `resources/views/admin/dashboard.blade.php` | Admin dashboard stub |
| `resources/views/staff/home.blade.php` | Staff home stub |
| `resources/views/proctor/home.blade.php` | Proctor home stub |

---

## 6. Out of Scope (Skeleton)

- Full CRUD UIs (periods, courses, rooms, etc.)
- Applicant encode form
- Scan page
- Audit log browser
- Fancy loading states (simple spinner is enough)
- Remember-me, password reset

---

## 7. Success Criteria

1. Guest visits `/` → redirect to `/login`.
2. Login with admin@example.com → redirect to `/admin/dashboard`; page shows API data (counts, sessions).
3. Logout → redirect to `/login`; visiting `/admin/dashboard` unauthenticated → 401 or redirect.
4. Login as staff → redirect to `/staff/home`; visiting `/admin/dashboard` → 403.
5. Login as proctor → redirect to `/proctor/home`; visiting `/admin/dashboard` → 403.

---

## 8. bd Tasks (Optional)

If tracking in beads:

- **Skeleton-1:** Web routes + auth redirect + login page
- **Skeleton-2:** Admin dashboard page (fetch API)
- **Skeleton-3:** Layout, logout, Staff/Proctor home stubs
- **Skeleton-4:** Test users seeder + RBAC verification

Can be done as one "skeleton frontend" task or split for incremental commits.
