<?php

/*
 * Composer's autoloader provides PHPUnit only. Application classes are loaded
 * by the app's own autoloader -- the same one production uses -- so the tests
 * exercise the real wiring rather than a Composer-only arrangement that would
 * not exist on the web host.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../htdocs/src/autoload.php';
