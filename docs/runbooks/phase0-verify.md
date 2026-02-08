# Phase 0 verification runbook

**Task:** [SecureCAT-sek] Run Phase 0 verification when Postgres and Redis available

Use **Docker (Laravel Sail)** for the easiest path — no local PostgreSQL, Redis, or PHP extensions needed.

---

## Option A: Docker (Sail) — recommended

**Prerequisites:** Docker (and WSL2 on Windows). From repo root, work in **`app/`**.

1. **Env and key**  
   ```bash
   cp .env.example .env
   ./vendor/bin/sail artisan key:generate
   ```
2. **Start containers**  
   ```bash
   ./vendor/bin/sail up -d
   ```
   Wait until `pgsql` and `redis` are healthy.
3. **Routes (smoke)**  
   ```bash
   ./vendor/bin/sail artisan route:list
   ```  
   Expect: list of routes including `GET|HEAD /`, Livewire routes, etc.
4. **Migrations**  
   ```bash
   ./vendor/bin/sail artisan migrate --no-interaction
   ```  
   Expect: default Laravel migrations run (users, cache, jobs tables).
5. **DB + Redis connectivity**  
   ```bash
   ./vendor/bin/sail artisan securecat:verify-env
   ```  
   Expect: `DB: OK` and `Redis: OK`, exit code 0.
6. **Welcome page**  
   Open [http://localhost](http://localhost) (or the port in `APP_PORT`, default 80). Confirm the Laravel welcome page and TALL stack check (Livewire counter) work.

---

## Option B: Without Docker (local PHP, Postgres, Redis)

**Prerequisites:** PHP with `pdo_pgsql` and phpredis (or predis); PostgreSQL and Redis running; database created (e.g. `createdb securecat`). `.env` with `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`.

Run from **`app/`**:

1. `php artisan route:list`
2. `php artisan migrate --no-interaction`
3. `php artisan securecat:verify-env`
4. `php artisan serve` → open the URL shown in a browser and confirm the welcome page.

---

## When done

- If all steps succeed: close the task with  
  `bd close SecureCAT-sek`
- If something fails: fix env or Docker, re-run the failing step; update the task if you need to track blockers.
