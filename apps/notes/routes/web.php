<?php

use AppCore\Contracts\PluginConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('notes::index', [
    'notes' => DB::table('notes')->latest()->limit((int) app(PluginConfig::class)->get('notes', 'per_page', 10))->get(),
]))->name('index');

Route::get('/hello', fn () => response('notes-hello'))->name('hello');
