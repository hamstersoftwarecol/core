<?php

return [
    'apps_path' => env('PLUGINS_PATH', base_path('apps')),
    // Rutas relativas se resuelven contra base_path (los tests usan su propio archivo).
    'cache_file' => (function () {
        $file = env('PLUGINS_CACHE_FILE');

        return match (true) {
            ! $file => app()->bootstrapPath('cache/active-apps.php'),
            str_starts_with($file, '/') => $file,
            default => base_path($file),
        };
    })(),
];
