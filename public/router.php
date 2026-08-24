<?php

# Legacy helper for local `php -S` only. Production Railway uses Nginx + PHP-FPM
# (see Dockerfile + scripts/railway-entrypoint.sh). Do not use this as the
# production router: prefer Nginx try_files → public/index.php.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri !== '/' && $uri !== '') {
    $path = __DIR__.$uri;
    if (is_file($path)) {
        return false;
    }
}

require __DIR__.'/index.php';
