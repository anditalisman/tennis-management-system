#!/bin/sh
set -e

# backend_vendor is a named volume (see docker-compose.yml) kept separate
# from the ./backend bind mount so the host checkout never shadows the
# image's compiled vendor/. But that means it only gets the image's vendor/
# the first time Docker creates it — after that it's a fixed snapshot that
# silently drifts from composer.lock on every deploy that adds/changes a
# dependency (this is how intervention/image ended up missing in
# production even though composer.lock had it). Reinstall whenever the
# lock file content changes, and use flock so app/queue/scheduler — which
# all start concurrently and share this same volume — don't run
# `composer install` against it at the same time.
VENDOR_DIR=/var/www/html/vendor
LOCK_FILE="$VENDOR_DIR/.composer-install.lock"
HASH_FILE="$VENDOR_DIR/.composer-lock.hash"

mkdir -p "$VENDOR_DIR"

(
    flock 9
    CURRENT_HASH=$(md5sum /var/www/html/composer.lock | awk '{print $1}')
    if [ ! -f "$HASH_FILE" ] || [ "$(cat "$HASH_FILE")" != "$CURRENT_HASH" ]; then
        echo "[entrypoint] composer.lock changed, running composer install..."
        composer install --no-interaction --prefer-dist
        echo "$CURRENT_HASH" > "$HASH_FILE"
    fi
) 9>"$LOCK_FILE"

# Migrations used to be a manual post-deploy step (docs/deployment.md §2) —
# easy to forget, and exactly what left a freshly deployed feature's tables
# missing in production (turnamen-kemerdekaan: routes/code deployed fine,
# but its endpoints had no schema to query yet). Run pending migrations
# automatically instead, guarded by the same flock pattern as composer
# install above so only one of the concurrently-starting app/queue/scheduler
# containers runs them per deploy.
MIGRATE_LOCK_FILE="$VENDOR_DIR/.migrate.lock"
(
    flock 9
    echo "[entrypoint] running pending migrations..."
    php artisan migrate --force
) 9>"$MIGRATE_LOCK_FILE"

exec "$@"
