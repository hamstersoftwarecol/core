<?php

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Contracts\PluginConfig;
use AppCore\Contracts\PluginState;
use AppCore\Contracts\Registry\ContentTypeRegistry;
use AppCore\Contracts\Registry\MenuItem;
use AppCore\Contracts\Registry\MenuRegistry;
use AppCore\Contracts\Registry\WidgetDefinition;
use AppCore\Contracts\Registry\WidgetRegistry;
use AppCore\Core\Http\Livewire\Admin\AppConfigPage;
use AppCore\Core\Models\ContentTypeRecord;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;
use AppCore\Core\Support\ActiveAppsCache;
use AppCore\Core\View\DashboardWidgets;
use AppCore\Notes\Widgets\NotesCountWidget;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;

// --- Menú ---------------------------------------------------------------

test('la entrada de menu aparece en el nav para usuarios con permiso', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(userWithPermission('notes.access'))
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('/apps/notes', false)
        ->assertSeeText('Notas');
});

test('el menu se oculta sin permiso y al desactivar', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    expect(app(MenuRegistry::class)->forUser(plainUser()))->toBe([]);

    $user = userWithPermission('notes.access');
    expect(app(MenuRegistry::class)->forUser($user))->toHaveCount(1);

    app(PluginManager::class)->deactivate('notes');
    expect(app(MenuRegistry::class)->forUser($user->fresh()))->toBe([]);
});

test('el menu ordena por order y no duplica en re-boot', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();
    bootNotesProvider();

    app(MenuRegistry::class)->add(new MenuItem('notes', 'Primero', 'apps.notes.hello', order: 1));

    $labels = array_map(fn ($i) => $i->label, app(MenuRegistry::class)->forUser(adminUser()));
    expect($labels)->toBe(['Primero', 'Notas']);
});

test('index de notes responde 200 con permiso', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(userWithPermission('notes.access'))->get('/apps/notes')->assertOk()->assertSeeText('Aún no hay notas');
});

// --- Widgets ------------------------------------------------------------

test('el dashboard renderiza widgets de apps activas con permiso', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    $this->actingAs(userWithPermission('notes.access'))->get('/dashboard')->assertOk()->assertSeeText('Notas');
    expect(app(WidgetRegistry::class)->forUser(plainUser()))->toBe([]);
});

test('un widget que lanza excepcion no rompe el dashboard', function () {
    app(PluginManager::class)->activate('notes');
    bootNotesProvider();

    app(WidgetRegistry::class)->add(new WidgetDefinition('notes', BrokenWidget::class, order: 0));

    $user = userWithPermission('notes.access');
    $this->actingAs($user);

    $component = app()->make(DashboardWidgets::class);
    expect($component->rendered)->toHaveCount(1); // solo NotesCountWidget

    $this->get('/dashboard')->assertOk();
});

class BrokenWidget extends Component
{
    public function render()
    {
        throw new RuntimeException('widget roto');
    }
}

// --- Tipos de contenido -------------------------------------------------

test('activar siembra content_types, desactivar los oculta y desinstalar los borra', function () {
    $manager = app(PluginManager::class);
    $manager->activate('notes');

    expect(ContentTypeRecord::where('slug', 'notes.note')->value('label'))->toBe('Nota')
        ->and(app(ContentTypeRegistry::class)->find('notes.note')?->config)->toBe(['table' => 'notes']);

    $manager->deactivate('notes');
    expect(app(ContentTypeRegistry::class)->all())->toBe([])
        ->and(ContentTypeRecord::count())->toBe(1);

    $manager->uninstall('notes');
    expect(ContentTypeRecord::count())->toBe(0);
});

test('manifest rechaza content types sin prefijo y config con tipo invalido', function () {
    expect(fn () => Manifest::fromArray(['slug' => 'a', 'name' => 'A', 'version' => '1', 'content_types' => ['b.post' => []]], '/tmp'))
        ->toThrow(InvalidManifest::class);
    expect(fn () => Manifest::fromFile(base_path('tests/Fixtures/apps/sample-bad-config/app.json')))
        ->toThrow(InvalidManifest::class);
});

// --- Configuración ------------------------------------------------------

test('config usa defaults del manifest y guarda valores validados', function () {
    $manager = app(PluginManager::class);
    $manager->activate('notes');

    expect(app(PluginConfig::class)->get('notes', 'per_page'))->toBe(10);

    expect($manager->saveConfig('notes', ['per_page' => '25']))->toBe(['per_page' => 25])
        ->and(app(PluginConfig::class)->get('notes', 'per_page'))->toBe(25);

    expect(fn () => $manager->saveConfig('notes', ['per_page' => 'abc']))->toThrow(InvalidArgumentException::class);
});

test('pantalla de config: solo super-admin, guarda via livewire', function () {
    app(PluginManager::class)->activate('notes');

    $this->get('/admin/apps/notes/config')->assertRedirect('/login');
    $this->actingAs(plainUser())->get('/admin/apps/notes/config')->assertForbidden();
    $this->actingAs(adminUser())->get('/admin/apps/notes/config')->assertOk()->assertSeeText('Notas por página');
    $this->actingAs(adminUser())->get('/admin/apps/nope/config')->assertNotFound();

    Livewire::test(AppConfigPage::class, ['slug' => 'notes'])
        ->set('values.per_page', 5)
        ->call('save')
        ->assertSet('flashType', 'success');

    expect(app(PluginConfig::class)->get('notes', 'per_page'))->toBe(5);
});

// --- CLI ----------------------------------------------------------------

test('apps:list, apps:activate y apps:deactivate', function () {
    $this->artisan('apps:list')->expectsOutputToContain('notes')->assertSuccessful();
    $this->artisan('apps:activate notes')->expectsOutputToContain('activada')->assertSuccessful();
    expect(app(PluginState::class)->isActive('notes'))->toBeTrue();

    $this->artisan('apps:deactivate notes')->assertSuccessful();
    expect(app(PluginState::class)->isActive('notes'))->toBeFalse();

    $this->artisan('apps:activate nope')->assertFailed();
    $this->artisan('apps:deactivate nope')->assertFailed();
});

test('apps:verify marca error las apps activas que ya no cumplen y reconstruye cache', function () {
    app(PluginManager::class)->activate('notes');
    $cache = app(ActiveAppsCache::class);
    unlink($cache->cacheFile());

    $this->artisan('apps:verify')->expectsOutputToContain('notes')->assertSuccessful();
    expect($cache->isActive('notes'))->toBeTrue();

    // una app "activa" en BD cuyo paquete desapareció del disco
    PluginApp::create(['slug' => 'ghost', 'name' => 'G', 'version' => '1', 'status' => 'active']);
    $this->artisan('apps:verify')->assertFailed();
    expect(PluginApp::where('slug', 'ghost')->value('status'))->toBe('error');
});

test('core:make-admin asigna super-admin a un usuario existente', function () {
    $user = plainUser();

    $this->artisan('core:make-admin', ['email' => $user->email])->assertSuccessful();
    expect($user->fresh()->hasRole('super-admin'))->toBeTrue();

    $this->artisan('core:make-admin', ['email' => 'nadie@example.com'])->assertFailed();
});

test('apps:verify aplica migraciones nuevas de una app actualizada y fija la version', function () {
    // copia de notes en un apps_path temporal dentro de base_path (los --path son relativos)
    $dir = base_path('storage/framework/testing/apps-upgrade');
    File::deleteDirectory($dir);
    File::copyDirectory(base_path('apps/notes'), $dir.'/notes');
    config(['plugin-core.apps_path' => $dir]);

    $manager = app(PluginManager::class);
    $manager->activate('notes');
    $activatedAt = PluginApp::where('slug', 'notes')->value('activated_at');

    // "deploy": nueva versión con una migración extra
    $manifest = json_decode(file_get_contents($dir.'/notes/app.json'), true);
    $manifest['version'] = '2.0.0';
    file_put_contents($dir.'/notes/app.json', json_encode($manifest));
    file_put_contents($dir.'/notes/database/migrations/2026_10_14_000001_add_pinned_to_notes.php', <<<'PHP'
        <?php
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;
        return new class extends Migration {
            public function up(): void { Schema::table('notes', fn (Blueprint $t) => $t->boolean('pinned')->default(false)); }
            public function down(): void { Schema::table('notes', fn (Blueprint $t) => $t->dropColumn('pinned')); }
        };
        PHP);

    $row = collect($manager->rowsWithStatus())->firstWhere('slug', 'notes');
    expect($row['version'])->toBe('2.0.0')->and($row['installed_version'])->toBe('1.1.0');

    $this->artisan('apps:verify')->assertSuccessful();

    expect(Schema::hasColumn('notes', 'pinned'))->toBeTrue()
        ->and(PluginApp::where('slug', 'notes')->value('version'))->toBe('2.0.0')
        ->and(PluginApp::where('slug', 'notes')->value('activated_at'))->toEqual($activatedAt);

    $manager->uninstall('notes');
    File::deleteDirectory($dir);
});
