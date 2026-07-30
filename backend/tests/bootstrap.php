<?php

// docker-compose.yml forwards real runtime config (DB_CONNECTION, CACHE_STORE,
// QUEUE_CONNECTION, etc.) into the container's OS environment so the app
// works without a physical backend/.env file. Those land in $_SERVER (PHP
// populates it from the process environment at startup). PHPUnit's own
// <php><env force="true"> handling (which runs before this file, per
// PHPUnit\TextUI\Application) already corrects putenv()/getenv()/$_ENV for
// the keys phpunit.xml declares — but it never touches $_SERVER, and
// phpdotenv's default adapter chain reads $_SERVER first. So without this,
// the container's real values win over phpunit.xml's testing config
// (sqlite/array/sync/etc.) regardless of `force`. Sync $_SERVER to match
// the already-correct $_ENV for exactly those keys (not a blanket unset —
// that would just make Laravel fall through to backend/.env instead, which
// mirrors the container's dev values and has the same problem).
foreach ([
    'APP_ENV', 'APP_MAINTENANCE_DRIVER', 'BCRYPT_ROUNDS', 'BROADCAST_CONNECTION',
    'CACHE_STORE', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'FILESYSTEM_DISK',
    'MAIL_MAILER', 'QUEUE_CONNECTION', 'SESSION_DRIVER',
    'PULSE_ENABLED', 'TELESCOPE_ENABLED', 'NIGHTWATCH_ENABLED',
] as $key) {
    if (array_key_exists($key, $_ENV)) {
        $_SERVER[$key] = $_ENV[$key];
    } else {
        unset($_SERVER[$key]);
    }
}

require __DIR__.'/../vendor/autoload.php';
