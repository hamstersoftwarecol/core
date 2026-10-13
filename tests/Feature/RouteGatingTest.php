<?php

use AppCore\Core\PluginManager;

test('app activa con permiso responde 200', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(userWithPermission('notes.access'))
        ->get('/apps/notes/hello')
        ->assertOk()
        ->assertSeeText('notes-hello');
});

test('app activa sin permiso responde 403', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(plainUser())
        ->get('/apps/notes/hello')
        ->assertForbidden();
});

test('super-admin accede sin permiso explicito', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(adminUser())
        ->get('/apps/notes/hello')
        ->assertOk();
});

test('app inactiva responde 404 aunque su provider este cargado', function () {
    bootNotesProvider(); // inactiva: no registra rutas
    $this->actingAs(adminUser())->get('/apps/notes/hello')->assertNotFound();

    // caso defensa-en-profundidad: rutas registradas (activa al boot) y luego desactivada
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();
    app(PluginManager::class)->deactivate('notes');

    $this->actingAs(adminUser())->get('/apps/notes/hello')->assertNotFound();
});

test('invitado es redirigido al login', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->get('/apps/notes/hello')->assertRedirect('/login');
});
