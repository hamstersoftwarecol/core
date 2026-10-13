<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // El estado de apps activas vive en un archivo; borrarlo ANTES de que la app
        // arranque garantiza que cada test parte con todas las apps inactivas.
        $cache = dirname(__DIR__).'/bootstrap/cache/active-apps.php';
        if (is_file($cache)) {
            unlink($cache);
        }

        parent::setUp();
    }
}
