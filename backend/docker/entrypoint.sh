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

exec "$@"
