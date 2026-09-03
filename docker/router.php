<?php

/**
 * Router for PHP's built-in server (Docker).
 * Avoids `php artisan serve`, which restarts when setup writes `.env`
 * and aborts the installation request mid-flight.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$file = __DIR__.'/../public'.$uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__.'/../public/index.php';
