#!/bin/sh
# Roundcube's official image regenerates config/config.inc.php from
# ROUNDCUBEMAIL_* env vars on every container start (it isn't persisted in a
# volume), so this can't just be a one-off manual edit — it has to run via
# the image's documented /entrypoint-tasks/post-setup hook, after that file
# is written, appending our own override at the end.
#
# mailserver's IMAP/SMTP use a self-signed cert (see docker-compose.yml's
# "mailserver" service comment) — without this, Roundcube's TLS handshake
# to it fails outright ("Connection to storage server failed" at login).
cat >> /var/www/html/config/config.inc.php <<'PHP'
$config['imap_conn_options'] = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
];
$config['smtp_conn_options'] = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
];
PHP
