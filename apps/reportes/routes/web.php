<?php

use AppCore\Contracts\PluginConfig;
use AppCore\Reportes\ContentStats;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (ContentStats $stats, PluginConfig $config) => view('reportes::index', [
    'rows' => $stats->rows((bool) $config->get('reportes', 'show_empty', true)),
]))->name('index');
