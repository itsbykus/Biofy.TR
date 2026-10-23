#!/bin/sh
# Biofy.TR container entrypoint (Coolify / Docker).
# 1. prepares the persistent volumes (storage/, public/media/) and their ownership,
# 2. installs on the first start or backs up + migrates on later starts (biofy:container-setup),
# 3. builds Laravel's config/route/view/event caches from the current environment,
# 4. starts the scheduler (optional) and Apache.
# Any failure stops the container before it serves traffic, so Coolify keeps the previous version running.
set -eu

cd /var/www/html

# Other commands (e.g. "php artisan ...") run as given.
if [ "${1:-}" != "apache2-foreground" ]; then
    exec "$@"
fi

as_app() {
    setpriv --reuid=www-data --regid=www-data --init-groups -- "$@"
}

# Fresh volumes may be empty: recreate the layout Laravel and Biofy.TR expect.
for dir in storage/app/private/backups storage/app/public storage/framework/cache/data \
           storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/media; do
    mkdir -p "$dir"
done
[ -f public/media/.htaccess ] || cp /usr/local/share/biofy/media.htaccess public/media/.htaccess

# Only the web server user may read/write runtime data; application code stays root-owned and read-only.
find storage bootstrap/cache public/media \( ! -user www-data -o ! -group www-data \) -exec chown www-data:www-data {} +
chmod -R u+rwX,g+rX,g-w,o-rwx storage bootstrap/cache public/media

# Drop caches left by a previous container/image; they are rebuilt below from the current environment.
as_app php artisan config:clear >/dev/null
as_app php artisan route:clear >/dev/null
as_app php artisan view:clear >/dev/null

as_app php artisan biofy:container-setup
as_app php artisan optimize

if [ "${BIOFY_SCHEDULER:-true}" = "true" ]; then
    # Runs "schedule:run" every minute (pruning, account deletions, domain re-checks, scheduled backups).
    as_app php artisan schedule:work &
fi

exec apache2-foreground
