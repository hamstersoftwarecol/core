<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // El estado de apps activas vive en un archivo; borrarlo ANTES de que la app
        // arranque garantiza que cada test parte con todas las apps inactivas.
        // Es el archivo de TEST (phpunit.xml: PLUGINS_CACHE_FILE), nunca el de desarrollo.
        $cache = dirname(__DIR__).'/'.($_SERVER['PLUGINS_CACHE_FILE'] ?? $_ENV['PLUGINS_CACHE_FILE'] ?? 'bootstrap/cache/active-apps.testing.php');
        if (is_file($cache)) {
            unlink($cache);
        }

        parent::setUp();
    }
}
