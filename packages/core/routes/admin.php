<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/apps', fn () => view('core::admin.apps'))->name('apps.index');
        Route::get('/apps/{slug}/config', fn (string $slug) => view('core::admin.app-config', ['slug' => $slug]))
            ->where('slug', '[a-z0-9][a-z0-9-]*')
            ->name('apps.config');
        Route::get('/roles', fn () => view('core::admin.roles'))->name('roles.index');
        Route::get('/users', fn () => view('core::admin.users'))->name('users.index');
    });
