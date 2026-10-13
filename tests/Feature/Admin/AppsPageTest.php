<?php

use AppCore\Core\Http\Livewire\Admin\AppsPage;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

test('invitado es redirigido al login', function () {
    $this->get('/admin/apps')->assertRedirect('/login');
});

test('usuario sin rol super-admin recibe 403', function () {
    $this->actingAs(plainUser())->get('/admin/apps')->assertForbidden();
});

test('super-admin ve el listado con las apps descubiertas', function () {
    // apps_path por defecto: solo apps/notes vive ahí (los fixtures están en tests/Fixtures)
    $this->actingAs(adminUser())
        ->get('/admin/apps')
        ->assertOk()
        ->assertSee('Notas')
        ->assertSee('notes');
});

test('el listado muestra slug, version y estado por fila', function () {
    $rows = collect(app(PluginManager::class)->rowsWithStatus());

    $notes = $rows->firstWhere('slug', 'notes');
    expect($notes)->not->toBeNull()
        ->and($notes['version'])->toBe('1.0.0')
        ->and($notes['status'])->toBe(PluginApp::STATUS_INACTIVE);
});

test('el componente activa una app', function () {
    Livewire::test(AppsPage::class)
        ->call('activate', 'notes')
        ->assertSet('flashType', 'success')
        ->assertSet('flash', 'App "notes" activada.')
        ->assertDispatched('toast');

    expect(PluginApp::where('slug', 'notes')->value('status'))->toBe(PluginApp::STATUS_ACTIVE)
        ->and(Schema::hasTable('notes'))->toBeTrue();
});

test('el componente desactiva una app conservando datos', function () {
    app(PluginManager::class)->activate('notes');
    DB::table('notes')->insert(['title' => 'viva', 'created_at' => now(), 'updated_at' => now()]);

    Livewire::test(AppsPage::class)
        ->call('deactivate', 'notes')
        ->assertSet('flashType', 'success');

    expect(Schema::hasTable('notes'))->toBeTrue()
        ->and(DB::table('notes')->count())->toBe(1)
        ->and(PluginApp::where('slug', 'notes')->value('status'))->toBe(PluginApp::STATUS_INACTIVE);
});

test('una app con migracion rota queda en error y el componente lo reporta', function () {
    config()->set('plugin-core.apps_path', base_path('tests/Fixtures/apps'));

    Livewire::test(AppsPage::class)
        ->call('activate', 'sample-broken')
        ->assertSet('flashType', 'error')
        ->assertDispatched('toast');

    expect(PluginApp::where('slug', 'sample-broken')->value('status'))->toBe(PluginApp::STATUS_ERROR);

    // reintento: vuelve a fallar limpiamente (no revienta el componente)
    Livewire::test(AppsPage::class)
        ->call('activate', 'sample-broken')
        ->assertSet('flashType', 'error');
});

test('desinstalar exige confirmacion previa', function () {
    Livewire::test(AppsPage::class)
        ->call('activate', 'notes')
        ->call('confirmUninstall', 'notes')
        ->assertSet('confirming', 'uninstall')
        ->call('uninstall', 'notes')
        ->assertSet('confirming', '');

    expect(Schema::hasTable('notes'))->toBeFalse()
        ->and(app(ActiveAppsCache::class)->isActive('notes'))->toBeFalse()
        ->and(PluginApp::where('slug', 'notes')->value('status'))->toBe(PluginApp::STATUS_INACTIVE);
});

test('uninstall sin confirmacion no borra nada', function () {
    app(PluginManager::class)->activate('notes');

    Livewire::test(AppsPage::class)->call('uninstall', 'notes');

    expect(PluginApp::where('slug', 'notes')->exists())->toBeTrue();
});
