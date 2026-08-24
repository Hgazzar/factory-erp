<?php

/**
 * PHP built-in server router (Railway / Docker).
 *
 * php -S ... -t public public/index.php يكسر الملفات الثابتة (CSS/JS):
 * كل الطلبات تدخل Laravel وقد تُحوَّل إلى /login.
 * هذا الراوتر يقدّم الملفات الموجودة كما هي، وغير ذلك → index.php.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($uri !== '/' && $uri !== '') {
    $path = __DIR__.$uri;
    if (is_file($path)) {
        return false;
    }
}

require __DIR__.'/index.php';
