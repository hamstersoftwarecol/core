<?php

return [
    'apps_path' => env('PLUGINS_PATH', base_path('apps')),
    'cache_file' => env('PLUGINS_CACHE_FILE', app()->bootstrapPath('cache/active-apps.php')),
];
