# Agent Instructions

## Local dev environment

- **Docker on Windows, all via docker exec** — Everything runs in containers. Laravel/PHP, PostgreSQL, Redis are all in Docker. Run artisan and tests by exec’ing into the app container (e.g. `docker exec -it <app-container> php artisan db:seed`). Not Sail, not host PHP.
- **Database has no users by default** — For local testing or browser/API tests that hit the real DB, run **`docker exec -it <app-container> php artisan db:seed`** (or `migrate:fresh --seed`) so the database has admin, staff, proctor, and demo periods/courses. Login: **admin@example.com** / **password**.

## Issue Tracking

This project uses **bd (beads)** for issue tracking.
Run `bd prime` for full workflow context. Once the repo is under git, run `bd hooks install` for auto-injection at session start.

**Quick reference:**
- `bd ready` - Find unblocked work
- `bd show <id>` - View issue details
- `bd update <id> --status in_progress` - Claim work
- `bd close <id>` - Complete work
- `bd sync` - Sync with git (run at session end)

For full workflow details: `bd prime`

## Landing the Plane (Session Completion)

**When ending a work session**, you MUST complete ALL steps below. Work is NOT complete until `git push` succeeds.

**MANDATORY WORKFLOW:**

1. **File issues for remaining work** - Create issues for anything that needs follow-up
2. **Run quality gates** (if code changed) - Tests, linters, builds
3. **Update issue status** - Close finished work, update in-progress items
4. **PUSH TO REMOTE** - This is MANDATORY:
   ```bash
   git pull --rebase
   bd sync
   git push
   git status  # MUST show "up to date with origin"
   ```
5. **Clean up** - Clear stashes, prune remote branches
6. **Verify** - All changes committed AND pushed
7. **Hand off** - Provide context for next session

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing - that leaves work stranded locally
- NEVER say "ready to push when you are" - YOU must push
- If push fails, resolve and retry until it succeeds

