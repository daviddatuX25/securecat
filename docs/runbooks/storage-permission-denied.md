# Fix: Storage permission denied (Blade / framework views)

**Symptom:** `file_put_contents(.../storage/framework/views/....php): Failed to open stream: Permission denied` when loading a page (e.g. `/register`).

**Cause:** The app runs in Docker (Sail). The bind-mounted `storage` (and sometimes `bootstrap/cache`) directory is not writable by the PHP process inside the container (e.g. user `sail` or `www-data`). This often appears after editing views or refactors, when Laravel recompiles Blade templates.

**Fix (run from repo root, with Sail containers up):**

```bash
cd app
./vendor/bin/sail exec laravel.test chmod -R 775 storage bootstrap/cache
./vendor/bin/sail exec laravel.test chown -R sail:sail storage bootstrap/cache
```

If you use `docker exec` directly (no Sail), replace `<app-container-name>` with your Laravel container (e.g. `app-laravel.test-1` when the compose project name is `app`):

```bash
docker exec -it <app-container-name> chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker exec -it <app-container-name> chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache
```

**On Windows bind mounts:** `chown` may not persist across restarts. If the error returns after restarting containers, run the `chmod` line again. As a last resort for local dev only, you can use `chmod -R 777 storage bootstrap/cache` so any user in the container can write (do not use 777 in production).

**Optional – clear compiled views** so Laravel regenerates them with correct permissions:

```bash
./vendor/bin/sail exec laravel.test php artisan view:clear
```

Then reload the page.
