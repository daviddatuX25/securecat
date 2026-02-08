# Phase 0 — Project Bootstrap

> Laravel + TALL stack setup before Phase 1 feature work. All Phase 1 tasks depend on a working dev environment.

**References:** [DECISIONS.md](../DECISIONS.md) ADR-003 (Laravel + TALL), [PHASE-1-TASKS.md](../backlog/PHASE-1-TASKS.md).

---

## 1. Goal

Deliver a runnable Laravel application with:

- Laravel (latest stable)
- TALL stack: Tailwind CSS, Alpine.js, Livewire
- PostgreSQL configured
- Redis configured (sessions)
- Development environment usable for Phase 1 migrations and feature work

---

## 2. Tasks

| Task ID | Summary |
|---------|---------|
| T0.1 | Create Laravel project (composer create-project) |
| T0.2 | Install and configure TALL stack (Livewire, Alpine, Tailwind) |
| T0.3 | Configure .env: PostgreSQL, Redis, APP_KEY, session driver |
| T0.4 | Verify app runs: DB connection, Redis connection, welcome page |

---

## 3. Task Details

### T0.1 — Create Laravel project

- `composer create-project laravel/laravel .` (or in app/ subdir per repo layout)
- PHP 8.2+ required (per Laravel 11+)
- Ensure `.env.example` present; `.env` gitignored

### T0.2 — Install and configure TALL stack

- Install Livewire: `composer require livewire/livewire`
- Install Livewire UI stack (Tailwind + Alpine): `php artisan livewire:install livewire` or manual:
  - Tailwind CSS (npm/vite)
  - Alpine.js (via Livewire or CDN for dev)
- Verify Blade + Livewire component rendering
- Align with [laravel-php-cursor-rules.mdc](../../.cursor/rules/laravel-php-cursor-rules.mdc)

### T0.3 — Configure environment

- `.env`: `DB_CONNECTION=pgsql`, `REDIS_CLIENT=phpredis` (or predis), `SESSION_DRIVER=redis`
- `APP_KEY` generated (`php artisan key:generate`)
- **Laravel Sail (Docker):** recommended — `php artisan sail:install --with=pgsql,redis`; then `./vendor/bin/sail up`, `sail artisan migrate`. No local PostgreSQL/Redis or PHP extensions required.
- Document required env vars in README or runbook

### T0.4 — Verify environment

- `php artisan migrate` (runs Laravel default migrations or placeholder)
- `php artisan tinker` → test DB and Redis connectivity
- Serve app; confirm welcome page loads
- Smoke: `php artisan route:list` shows routes

---

## 4. Dependencies

- T0.1 → T0.2 → T0.3 → T0.4 (sequential)
- T0.4 blocks all Phase 1 root tasks (T1.1.1, T2.1.1, T2.3.1, T6.1.1)

---

## 5. Definition of Done

- [ ] Laravel app created and runs locally
- [ ] TALL stack installed; Blade/Livewire/Tailwind/Alpine usable
- [ ] PostgreSQL and Redis connections verified
- [ ] `.env.example` documents all required vars
- [ ] `composer install && npm install` documented for new clones
