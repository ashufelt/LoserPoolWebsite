<?php

/*
 * Minimal PSR-4 style autoloader for the LoserPool\ namespace.
 *
 * Deliberately hand-rolled rather than Composer's: the app is deployed by
 * copying files to the web host, and requiring a `composer install` step on
 * the server would be a new way for a deploy to fail. Composer is used only
 * for dev tooling (PHPUnit) and never needs to exist in production.
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'LoserPool\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
