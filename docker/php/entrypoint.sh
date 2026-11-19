#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ugo+rwX storage bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php <<'PHP'
<?php

$host = getenv('DB_HOST') ?: 'mysql';
$db = getenv('DB_DATABASE') ?: 'weightfactor';
$user = getenv('DB_USERNAME') ?: 'weightfactor';
$pass = getenv('DB_PASSWORD') ?: 'secret';
$port = getenv('DB_PORT') ?: '3306';

for ($i = 0; $i < 60; $i++) {
    try {
        new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, "waiting for database...\n");
        sleep(1);
    }
}

fwrite(STDERR, "database not ready\n");
exit(1);
PHP

php artisan migrate --force --no-interaction
php artisan owner:sync --no-interaction

exec php-fpm
