<?php

use AppCore\Core\Models\ContentTypeRecord;
use AppCore\Core\PluginManager;
use AppCore\Reportes\ContentStats;
use Illuminate\Support\Facades\DB;

function activateBoth(): void
{
    app(PluginManager::class)->activate('notes');
    app(PluginManager::class)->activate('reportes');
    bootNotesProvider();
    bootReportesProvider();
}

test('reportes cuenta el contenido de notes sin importar codigo de notes', function () {
    activateBoth();
    DB::table('notes')->insert([
        ['title' => 'a', 'created_at' => now(), 'updated_at' => now()],
        ['title' => 'b', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(app(ContentStats::class)->rows())->toBe([
        ['slug' => 'notes.note', 'app' => 'notes', 'label' => 'Nota', 'count' => 2],
    ]);

    $this->actingAs(userWithPermission('reportes.access'))
        ->get('/apps/reportes')
        ->assertOk()
        ->assertSeeText('Nota')
        ->assertSeeText('notes.note');
});

test('al desactivar notes su tipo desaparece de reportes; al reactivar vuelve con los datos', function () {
    activateBoth();
    DB::table('notes')->insert(['title' => 'a', 'created_at' => now(), 'updated_at' => now()]);

    app(PluginManager::class)->deactivate('notes');
    expect(app(ContentStats::class)->rows())->toBe([]);

    $this->actingAs(userWithPermission('reportes.access'))
        ->get('/apps/reportes')->assertOk()->assertSeeText('Ninguna app activa');

    app(PluginManager::class)->activate('notes');
    expect(app(ContentStats::class)->total())->toBe(1);
});

test('tipos con tabla invalida o inexistente no rompen el reporte', function () {
    activateBoth();
    ContentTypeRecord::create(['slug' => 'notes.raro', 'app_slug' => 'notes', 'label' => 'Raro', 'config' => ['table' => 'notes; drop table users']]);
    ContentTypeRecord::create(['slug' => 'notes.fantasma', 'app_slug' => 'notes', 'label' => 'Fantasma', 'config' => ['table' => 'no_existe']]);

    $counts = collect(app(ContentStats::class)->rows())->pluck('count', 'slug')->all();
    expect($counts)->toBe(['notes.fantasma' => null, 'notes.note' => 0, 'notes.raro' => null]);

    expect(app(ContentStats::class)->rows(showEmpty: false))->toBe([]);
});

test('reportes funciona sin otras apps activas', function () {
    app(PluginManager::class)->activate('reportes');
    bootReportesProvider();

    $this->actingAs(adminUser())->get('/apps/reportes')->assertOk()->assertSeeText('Ninguna app activa');
});

test('permisos: sin reportes.access no ve menu, widget ni ruta', function () {
    activateBoth();
    $user = userWithPermission('notes.access');

    $this->actingAs($user)->get('/apps/reportes')->assertForbidden();
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('/apps/reportes', false)->assertDontSeeText('Contenido total');

    $this->actingAs(userWithPermission('reportes.access'))->get('/dashboard')
        ->assertSee('/apps/reportes', false)->assertSeeText('Contenido total');
});

test('aceptacion end-to-end via CLI: ambas apps activas y verify limpio', function () {
    $this->artisan('apps:activate notes')->assertSuccessful();
    $this->artisan('apps:activate reportes')->assertSuccessful();
    $this->artisan('apps:verify')->assertSuccessful();
    $this->artisan('apps:list')->expectsOutputToContain('reportes')->assertSuccessful();
});
