# Plugin Core Fase 1 (Kernel) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir el kernel del sistema de apps tipo plugin: Laravel 12 + Breeze (Livewire), paquetes `contracts`/`core`, PluginManager con activar/desactivar/desinstalar (migraciones + permisos + cache), gating de rutas bajo `/apps/{slug}` y panel admin `/admin/apps` — todo verificado con Pest.

**Architecture:** Monorepo con tres tipos de paquetes Composer locales: `packages/contracts` (única dependencia de las apps), `packages/core` (PluginManager, admin, middleware) y `apps/*` (apps descubiertas por `app.json`). Un `PluginManager` lee/escribe la tabla `apps` y un cache en archivo `bootstrap/cache/active-apps.php` (cero queries por request); los ServiceProviders de apps se auto-descubren pero solo registran rutas si el cache dice "activa".

**Tech Stack:** Laravel 12 (PHP 8.3+), Breeze stack Livewire, Flux (tier gratuito), Tailwind CSS 4, spatie/laravel-permission, composer/semver, Pest.

**Spec:** `docs/superpowers/specs/2026-10-13-laravel-plugin-core-design.md` — este plan argumenta desde el spec; el ejecutor debe leerlo.

## Global Constraints

- PHP `>=8.3`, Laravel `^12.0` (constraint del proyecto: `laravel/laravel:^12.0`).
- Breeze **stack Livewire**; Flux **solo tier gratuito** (sin `flux:activate`, sin auth.json).
- spatie/laravel-permission con rol `super-admin` (Gate::before: super-admin pasa cualquier permiso).
- Las apps importan **solo** `appcore/contracts` — nunca `App\*` ni `appcore/core`.
- Rutas de apps siempre bajo prefijo `/apps/{slug}` con middleware `web`, `auth`, `app.access:{slug}`.
- App inactiva → **404**; app activa sin permiso → **403**; `access` middleware re-verifica estado (defensa en profundidad).
- Desactivar **no borra datos**; desinstalar hace rollback de migraciones (`--path` + `--step`).
- Permisos spatie con prefijo obligatorio `{slug}.` (validado por `Manifest`).
- Todas las rutas/migraciones de apps viven bajo `base_path()` (los `--path` de Artisan son relativos a base).
- `requires.php` estilo `">=8.3"`; `requires.laravel` estilo `"^12.0"` (semver de composer vía `composer/semver`).
- Tests con Pest; la suite completa (`./vendor/bin/pest`) debe quedar en verde al final de cada tarea.
- Fuera de alcance (spec §10): multisitio, multi-tenant, marketplace, auto-update, Flux Pro. De esta fase quedan para Fase 2: CLI `apps:*`, API de menús, widgets, tipos de contenido, pantalla de configuración de apps y pantallas `/admin/roles` + `/admin/users` (en Fase 1 solo están los roles seed y la API spatie).
- Commits frecuentes: **un commit al final de cada tarea**.

## File Structure

```
packages/contracts/                     # appcore/contracts (AppCore\Contracts\)
  composer.json
  src/PluginState.php                   # interfaz: estado activo + permiso de acceso
  src/Manifest.php                      # parser/validador de app.json
  src/Exceptions/InvalidManifest.php
  src/PluginServiceProvider.php         # base abstracta con gating de activación

packages/core/                          # appcore/core (AppCore\Core\)
  composer.json
  config/plugin-core.php                # apps_path, cache_file
  database/migrations/2026_10_13_000001_create_apps_table.php
  src/CoreServiceProvider.php           # bindings, migraciones, vistas, rutas admin, Gate
  src/Models/PluginApp.php
  src/Support/ActiveAppsCache.php       # implements PluginState (archivo cache)
  src/PluginManager.php                 # discover/sync/activate/deactivate/uninstall
  src/Exceptions/AppNotFound.php
  src/Exceptions/ActivationFailed.php
  src/Http/Middleware/AppAccess.php
  src/Http/Livewire/Admin/AppsPage.php
  resources/views/admin/apps.blade.php
  routes/admin.php

apps/notes/                             # app de ejemplo (paquete Composer real)
  composer.json  app.json
  src/NotesServiceProvider.php
  routes/web.php
  database/migrations/2026_10_13_000001_create_notes_table.php

config/plugin-core.php                  # publicado en la app (Task 13 lo asegura)

bootstrap/app.php                        # aliases de middleware (role, permission, app.access)
app/Models/User.php                      # HasRoles
database/seeders/RolesSeeder.php
resources/views/layouts/app.blade.php    # link "Apps" en el sidebar/nav

tests/Pest.php  tests/TestCase.php  tests/Fixtures/apps/{sample,sample-broken,sample-req-laravel,sample-req-app}/
tests/Unit/{ManifestTest,ActiveAppsCacheTest,PluginServiceProviderTest}.php
tests/Feature/{PluginManagerTest,RouteGatingTest,RolesTest}.php
tests/Feature/Admin/AppsPageTest.php
```

---

### Task 1: Scaffold — Laravel 12 + git + Breeze (Livewire) + Pest + Tailwind 4 + Flux

**Files:**
- Create: proyecto Laravel completo en la raíz (moverá desde `tmp-laravel/`)
- Modify: `vite.config.js`, `resources/css/app.css`, `resources/views/layouts/app.blade.php`
- Modify: `composer.json`, `.gitignore`, `phpunit.xml`

**Interfaces:**
- Consumes: nada (punto de partida).
- Produces: app Laravel con login/registro/logout funcionando, Pest operativo, Tailwind 4 + Flux instalados, repo git inicializado. Todas las tareas siguientes asumen este estado.

- [ ] **Step 1: Preflight de herramientas**

Run: `php -v && composer -V && node -v && npm -v`
Expected: PHP >= 8.3, Composer 2.x, Node >= 20. Si algo falta, detener y reportarlo (no se continúa sin ellos).

- [ ] **Step 2: Crear el proyecto e instalarlo en la raíz**

```bash
composer create-project laravel/laravel tmp-laravel "^12.0" --no-interaction
shopt -s dotglob && mv tmp-laravel/* . && rmdir tmp-laravel
```

Verificar que `.env` existe y `APP_KEY` está seteada (`grep ^APP_KEY .env`). Si está vacía: `php artisan key:generate`.

- [ ] **Step 3: git init + commit inicial**

```bash
git init
grep -q "bootstrap/cache/\*.php" .gitignore || echo "/bootstrap/cache/*.php" >> .gitignore
git add -A
git commit -m "chore: scaffold Laravel 12"
```

Nota: el repo no existía antes; este es el commit inicial del proyecto.

- [ ] **Step 4: Breeze stack Livewire + Pest**

```bash
composer require laravel/breeze --dev --no-interaction
php artisan breeze:install livewire --pest --no-interaction
# si --pest no es reconocido por tu versión de Breeze: omitir el flag (el paso siguiente instala Pest)
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
[ -f tests/Pest.php ] || ./vendor/bin/pest --init
npm install && npm run build
```

Si `breeze:install` falla por constraint de Composer, revisar `composer show laravel/breeze --all` para la versión compatible con Laravel 12 y ajustar el constraint; reportar el hallazgo antes de continuar.
Si `tests/Pest.php` ya existe (Breeze lo creó con `--pest`), NO ejecutar `pest --init` (sobrescribiría la config).

- [ ] **Step 5: Verificar scaffold verde**

Run: `php artisan test`
Expected: PASS (tests por defecto de Laravel + tests de autenticación de Breeze con Pest).
Run: `php artisan route:list | grep login`
Expected: rutas `login`, `logout`, `register` presentes.

- [ ] **Step 6: Commit del scaffold**

```bash
git add -A && git commit -m "feat: Breeze (Livewire) + Pest + auth"
```

- [ ] **Step 7: Migrar el CSS de Breeze a Tailwind 4 (requisito de Flux ≥ 4.2)**

Breeze scaffoldea Tailwind 3; Flux v2 exige Tailwind ≥ 4.2.

```bash
git status --porcelain  # debe estar limpio (el tool de upgrade lo exige)
npm install tailwindcss@^4.2 @tailwindcss/vite@latest
npx @tailwindcss/upgrade@latest --force
```

Verificar el estado final y corregir a mano si el tool no dejó todo:

1. `vite.config.js` debe importar y usar el plugin:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

2. `resources/css/app.css` debe empezar con la sintaxis v4 (sin `@tailwind base/components/utilities`):

```css
@import 'tailwindcss';

/* componentes propios de Breeze (si los hay) se conservan debajo */
```

3. Eliminar si quedaron: `postcss.config.js`, `tailwind.config.js` (v4 es CSS-first; los `darkMode`/`theme` se declaran con `@theme`/`@custom-variant` en CSS si hacen falta).

Run: `npm run build && ls node_modules/tailwindcss/package.json && node -e "console.log(require('./node_modules/tailwindcss/package.json').version)"`
Expected: build OK y versión `>= 4.2.0`.

- [ ] **Step 8: Instalar y cablear Flux (tier gratuito)**

```bash
composer require livewire/flux --no-interaction
```

En `resources/views/layouts/app.blade.php`:
- Dentro de `<head>`, añadir `@fluxAppearance` (antes de `</head>`).
- Antes de `</body>`, añadir `@fluxScripts` (si el layout ya tiene `@livewireScripts`, dejar uno solo; no duplicar).

En `resources/css/app.css`, dejar exactamente (más los estilos propios que ya tuviera):

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';
@custom-variant dark (&:where(.dark, .dark *));
```

- [ ] **Step 9: Verificación final de la tarea**

Run: `npm run build && php artisan test && git status --porcelain`
Expected: build OK, suite en verde, working tree limpio para commit.

```bash
git add -A && git commit -m "feat: Tailwind 4 + Flux (free tier)"
```

---

### Task 2: Paquetes `contracts` y `core` como path repositories

**Files:**
- Create: `packages/contracts/composer.json`
- Create: `packages/core/composer.json`
- Create: `packages/core/config/plugin-core.php`
- Create: `packages/core/src/CoreServiceProvider.php`
- Modify: `composer.json` (repositories + require)
- Test: `tests/Feature/CorePackageTest.php`

**Interfaces:**
- Consumes: Task 1.
- Produces: `config('plugin-core.apps_path')` (= `base_path('apps')`), `config('plugin-core.cache_file')` (= `bootstrap_path('cache/active-apps.php')`), `CoreServiceProvider` auto-descubierto. Las tareas siguientes añaden bindings a `CoreServiceProvider`.

- [ ] **Step 1: Escribir el test fallido**

```php
<?php
// tests/Feature/CorePackageTest.php
use AppCore\Core\CoreServiceProvider;

test('el paquete core esta registrado y su config se fusiona', function () {
    expect(config('plugin-core.apps_path'))->toBe(base_path('apps'))
        ->and(config('plugin-core.cache_file'))->toBe(bootstrap_path('cache/active-apps.php'))
        ->and(app()->getProviders(CoreServiceProvider::class))->not->toBeEmpty();
});
```

- [ ] **Step 2: Correr el test y verlo fallar**

Run: `./vendor/bin/pest tests/Feature/CorePackageTest.php`
Expected: FAIL — `Class "AppCore\Core\CoreServiceProvider" not found`.

- [ ] **Step 3: Implementar los paquetes**

`packages/contracts/composer.json`:

```json
{
    "name": "appcore/contracts",
    "description": "Contrato del sistema de apps del core",
    "type": "library",
    "version": "1.0.0",
    "require": {
        "php": ">=8.3",
        "illuminate/support": "^12.0",
        "illuminate/routing": "^12.0"
    },
    "autoload": { "psr-4": { "AppCore\\Contracts\\": "src/" } }
}
```

`packages/core/composer.json`:

```json
{
    "name": "appcore/core",
    "description": "Kernel del sistema de apps",
    "type": "library",
    "version": "1.0.0",
    "require": {
        "php": ">=8.3",
        "appcore/contracts": "^1.0",
        "illuminate/support": "^12.0",
        "illuminate/routing": "^12.0",
        "illuminate/http": "^12.0"
    },
    "autoload": { "psr-4": { "AppCore\\Core\\": "src/" } },
    "extra": { "laravel": { "providers": ["AppCore\\Core\\CoreServiceProvider"] } }
}
```

`packages/core/config/plugin-core.php`:

```php
<?php

return [
    'apps_path' => env('PLUGINS_PATH', base_path('apps')),
    'cache_file' => env('PLUGINS_CACHE_FILE', bootstrap_path('cache/active-apps.php')),
];
```

`packages/core/src/CoreServiceProvider.php`:

```php
<?php

namespace AppCore\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/plugin-core.php', 'plugin-core');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/plugin-core.php' => config_path('plugin-core.php'),
            ], 'plugin-core-config');
        }
    }
}
```

`composer.json` raíz — añadir dentro del array `repositories` (crear el array si no existe):

```json
"repositories": [
    { "type": "path", "url": "packages/*", "options": { "symlink": true } }
]
```

y en `require` añadir: `"appcore/core": "^1.0"`.

Run: `composer update --no-interaction`

- [ ] **Step 4: Correr el test y verlo pasar**

Run: `./vendor/bin/pest tests/Feature/CorePackageTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: paquetes contracts/core como path repositories"
```

---

### Task 3: Tabla `apps` + modelo `PluginApp`

**Files:**
- Create: `packages/core/database/migrations/2026_10_13_000001_create_apps_table.php`
- Create: `packages/core/src/Models/PluginApp.php`
- Modify: `packages/core/src/CoreServiceProvider.php` (`boot()` — loadMigrationsFrom)
- Test: `tests/Feature/AppsTableTest.php`

**Interfaces:**
- Consumes: Task 2.
- Produces: tabla `apps` (columnas `slug, name, version, status, last_error, config, activated_at`), modelo `AppCore\Core\Models\PluginApp` con `STATUS_ACTIVE='active' | STATUS_INACTIVE='inactive' | STATUS_ERROR='error'`, cast `config: array`, `activated_at: datetime`.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Feature/AppsTableTest.php
use AppCore\Core\Models\PluginApp;

test('la tabla apps existe y el modelo persiste con casts', function () {
    expect(Schema::hasTable('apps'))->toBeTrue();

    $row = PluginApp::create([
        'slug' => 'demo', 'name' => 'Demo', 'version' => '1.0.0',
        'status' => PluginApp::STATUS_INACTIVE,
        'config' => ['per_page' => 10],
    ]);

    expect($row->config)->toBe(['per_page' => 10])
        ->and($row->fresh()->status)->toBe('inactive');
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/AppsTableTest.php`
Expected: FAIL — table `apps` does not exist.

- [ ] **Step 3: Implementar**

`packages/core/database/migrations/2026_10_13_000001_create_apps_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('version', 32);
            $table->string('status', 16)->default('inactive')->index();
            $table->text('last_error')->nullable();
            $table->json('config')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
```

`packages/core/src/Models/PluginApp.php`:

```php
<?php

namespace AppCore\Core\Models;

use Illuminate\Database\Eloquent\Model;

class PluginApp extends Model
{
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_INACTIVE = 'inactive';
    public const string STATUS_ERROR = 'error';

    protected $table = 'apps';

    protected $fillable = ['slug', 'name', 'version', 'status', 'last_error', 'config'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'activated_at' => 'datetime',
        ];
    }
}
```

En `CoreServiceProvider::boot()`, añadir antes del `if ($this->app->runningInConsole())`:

```php
$this->loadMigrationsFrom(__DIR__.'/../database/migrations');
```

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/AppsTableTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: tabla apps y modelo PluginApp"
```

---

### Task 4: `Manifest` (parser y validador de `app.json`) + fixtures de prueba

**Files:**
- Create: `packages/contracts/src/Exceptions/InvalidManifest.php`
- Create: `packages/contracts/src/Manifest.php`
- Create: `packages/core/composer.json` (añadir `composer/semver`)
- Create fixtures: `tests/Fixtures/apps/{sample,sample-broken,sample-req-laravel,sample-req-app}/app.json` (+ migraciones de `sample` y `sample-broken`)
- Test: `tests/Unit/ManifestTest.php`

**Interfaces:**
- Consumes: Task 2 (paquetes instalados).
- Produces: `AppCore\Contracts\Manifest` con `Manifest::fromFile(string): Manifest`, `Manifest::fromArray(array, string $sourceDir): Manifest`, y métodos `slug(): string`, `name(): string`, `version(): string`, `permissions(): array`, `accessPermission(): string`, `requiredApps(): array`, `phpConstraint(): ?string`, `laravelConstraint(): ?string`, `configSchema(): array`, `seeder(): ?string`, `migrationsPath(): string` (absoluta), `sourceDir(): string`. Lanza `AppCore\Contracts\Exceptions\InvalidManifest`. El schema exige `slug` (`^[a-z0-9][a-z0-9-]*$`), `name`, `version`; permisos con prefijo `{slug}.`; `access` (opcional, default `{slug}.access`) siempre queda incluido en `permissions()`.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Unit/ManifestTest.php
use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;

function fixture_manifest_path(string $app): string
{
    return base_path("tests/Fixtures/apps/{$app}/app.json");
}

test('parsea un manifest valido', function () {
    $m = Manifest::fromFile(fixture_manifest_path('sample'));

    expect($m->slug())->toBe('sample')
        ->and($m->name())->toBe('Sample App')
        ->and($m->version())->toBe('1.0.0')
        ->and($m->permissions())->toBe(['sample.access'])
        ->and($m->accessPermission())->toBe('sample.access')
        ->and($m->requiredApps())->toBe([])
        ->and($m->phpConstraint())->toBe('>=8.3')
        ->and($m->laravelConstraint())->toBe('^12.0')
        ->and($m->migrationsPath())->toBe(base_path('tests/Fixtures/apps/sample/database/migrations'));
});

test('el permiso de acceso por defecto se agrega a permissions', function () {
    $m = Manifest::fromArray(
        ['slug' => 'demo', 'name' => 'Demo', 'version' => '1.0.0', 'permissions' => ['demo.write']],
        base_path('tests')
    );

    expect($m->accessPermission())->toBe('demo.access')
        ->and($m->permissions())->toContain('demo.access')
        ->and($m->permissions())->toContain('demo.write');
});

test('rechaza un manifest sin campos obligatorios', function () {
    Manifest::fromArray(['name' => 'X', 'version' => '1.0.0'], base_path());
})->throws(InvalidManifest::class, "app.json: falta el campo obligatorio 'slug'");

test('rechaza un slug invalido (previene path traversal)', function () {
    Manifest::fromArray(['slug' => '../etc', 'name' => 'X', 'version' => '1.0.0'], base_path());
})->throws(InvalidManifest::class, 'app.json: slug inválido \'../etc\' (solo minúsculas, números y guiones)');

test('rechaza permisos sin prefijo del slug', function () {
    Manifest::fromArray(
        ['slug' => 'demo', 'name' => 'Demo', 'version' => '1.0.0', 'permissions' => ['admin.delete']],
        base_path()
    );
})->throws(InvalidManifest::class, "app.json: el permiso 'admin.delete' debe empezar por 'demo.'");

test('rechaza json corrupto', function () {
    Manifest::fromFile(fixture_manifest_path('not-a-real-app'));
})->throws(InvalidManifest::class);
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Unit/ManifestTest.php`
Expected: FAIL — `Class "AppCore\Contracts\Manifest" not found` (los fixtures aún no existen).

- [ ] **Step 3: Implementar fixtures**

`tests/Fixtures/apps/sample/app.json`:

```json
{
    "slug": "sample",
    "name": "Sample App",
    "version": "1.0.0",
    "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
    "permissions": ["sample.access"],
    "config": { "items_per_page": { "type": "number", "default": 15 } },
    "migrations": "database/migrations"
}
```

`tests/Fixtures/apps/sample/database/migrations/2026_10_13_000001_create_sample_items_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_items');
    }
};
```

`tests/Fixtures/apps/sample-broken/app.json`:

```json
{
    "slug": "sample-broken",
    "name": "Sample Roto",
    "version": "1.0.0",
    "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
    "permissions": ["sample-broken.access"],
    "migrations": "database/migrations"
}
```

`tests/Fixtures/apps/sample-broken/database/migrations/2026_10_13_000001_create_broken_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        throw new RuntimeException('boom');
    }

    public function down(): void
    {
        //
    }
};
```

`tests/Fixtures/apps/sample-req-laravel/app.json`:

```json
{
    "slug": "sample-req-laravel",
    "name": "Requiere Laravel 99",
    "version": "1.0.0",
    "requires": { "php": ">=8.3", "laravel": "^99.0", "apps": [] },
    "permissions": ["sample-req-laravel.access"]
}
```

`tests/Fixtures/apps/sample-req-app/app.json`:

```json
{
    "slug": "sample-req-app",
    "name": "Requiere Sample",
    "version": "1.0.0",
    "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": ["sample"] },
    "permissions": ["sample-req-app.access"]
}
```

- [ ] **Step 4: Implementar `Manifest` e instalar `composer/semver`**

`packages/contracts/src/Exceptions/InvalidManifest.php`:

```php
<?php

namespace AppCore\Contracts\Exceptions;

use InvalidArgumentException;

class InvalidManifest extends InvalidArgumentException
{
}
```

`packages/contracts/src/Manifest.php`:

```php
<?php

namespace AppCore\Contracts;

use AppCore\Contracts\Exceptions\InvalidManifest;

final class Manifest
{
    private function __construct(
        private readonly array $data,
        private readonly string $sourceDir,
    ) {
    }

    public static function fromFile(string $file): self
    {
        if (! is_file($file)) {
            throw new InvalidManifest("No existe el manifiesto: {$file}");
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (! is_array($decoded)) {
            throw new InvalidManifest("JSON inválido en {$file}: ".json_last_error_msg());
        }

        return self::fromArray($decoded, dirname($file));
    }

    public static function fromArray(array $data, string $sourceDir): self
    {
        foreach (['slug', 'name', 'version'] as $key) {
            if (empty($data[$key]) || ! is_string($data[$key])) {
                throw new InvalidManifest("app.json: falta el campo obligatorio '{$key}'");
            }
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $data['slug'])) {
            throw new InvalidManifest(
                "app.json: slug inválido '{$data['slug']}' (solo minúsculas, números y guiones)"
            );
        }

        $access = $data['access'] ?? $data['slug'].'.access';
        $data['permissions'] = array_values(array_unique(array_merge($data['permissions'] ?? [], [$access])));

        foreach ($data['permissions'] as $permission) {
            if (! is_string($permission) || ! str_starts_with($permission, $data['slug'].'.')) {
                throw new InvalidManifest(
                    "app.json: el permiso '{$permission}' debe empezar por '{$data['slug']}.'"
                );
            }
        }

        return new self($data, rtrim($sourceDir, '/'));
    }

    public function slug(): string
    {
        return $this->data['slug'];
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function version(): string
    {
        return $this->data['version'];
    }

    public function permissions(): array
    {
        return $this->data['permissions'];
    }

    public function accessPermission(): string
    {
        return $this->data['access'] ?? $this->slug().'.access';
    }

    public function requiredApps(): array
    {
        return array_values($this->data['requires']['apps'] ?? []);
    }

    public function phpConstraint(): ?string
    {
        return $this->data['requires']['php'] ?? null;
    }

    public function laravelConstraint(): ?string
    {
        return $this->data['requires']['laravel'] ?? null;
    }

    public function configSchema(): array
    {
        return $this->data['config'] ?? [];
    }

    public function seeder(): ?string
    {
        return $this->data['seeder'] ?? null;
    }

    public function sourceDir(): string
    {
        return $this->sourceDir;
    }

    public function migrationsPath(): string
    {
        return $this->sourceDir.'/'.($this->data['migrations'] ?? 'database/migrations');
    }
}
```

En `packages/core/composer.json`, añadir a `require`: `"composer/semver": "^3.4"`.
Run: `composer update --no-interaction`

- [ ] **Step 5: Ver pasar**

Run: `./vendor/bin/pest tests/Unit/ManifestTest.php`
Expected: PASS (todos los casos).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: parser y validador de manifest app.json con fixtures"
```

---

### Task 5: `ActiveAppsCache` (implementa `PluginState`)

**Files:**
- Create: `packages/contracts/src/PluginState.php`
- Create: `packages/core/src/Support/ActiveAppsCache.php`
- Modify: `packages/core/src/CoreServiceProvider.php` (binding singleton + alias)
- Modify: `tests/TestCase.php` (borrar el cache ANTES de arrancar la app)
- Test: `tests/Unit/ActiveAppsCacheTest.php`

**Interfaces:**
- Consumes: `PluginApp` (Task 3), `Manifest` (Task 4), `config('plugin-core.cache_file')` y `config('plugin-core.apps_path')` (Task 2).
- Produces: `AppCore\Contracts\PluginState` con `isActive(string): bool` y `accessPermissionFor(string): ?string`; concreto `AppCore\Core\Support\ActiveAppsCache` con `rebuild(): array` (devuelve slugs activos) y `cacheFile(): string`. Binding: `app(ActiveAppsCache::class)` y `app(PluginState::class)` son el **mismo** singleton.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Unit/ActiveAppsCacheTest.php
use AppCore\Contracts\PluginState;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;

test('reconstruye el cache desde la base y expone permisos de acceso', function () {
    PluginApp::create(['slug' => 'sample', 'name' => 'Sample', 'version' => '1.0.0', 'status' => 'active']);
    PluginApp::create(['slug' => 'other', 'name' => 'Other', 'version' => '1.0.0', 'status' => 'inactive']);

    $cache = app(ActiveAppsCache::class);
    $active = $cache->rebuild();

    expect($active)->toBe(['sample'])
        ->and(is_file($cache->cacheFile()))->toBeTrue()
        ->and($cache->isActive('sample'))->toBeTrue()
        ->and($cache->isActive('other'))->toBeFalse()
        ->and($cache->accessPermissionFor('sample'))->toBe('sample.access')
        ->and($cache->accessPermissionFor('nope'))->toBeNull();
});

test('PluginState y ActiveAppsCache son el mismo singleton', function () {
    expect(app(PluginState::class))->toBe(app(ActiveAppsCache::class));
});

test('archivo ausente o corrupto devuelve estado vacio sin explotar', function () {
    $cache = app(ActiveAppsCache::class);
    file_put_contents($cache->cacheFile(), '<?php return "esto no es un array";');

    expect($cache->isActive('sample'))->toBeFalse()
        ->and($cache->accessPermissionFor('sample'))->toBeNull();

    unlink($cache->cacheFile());

    expect($cache->isActive('sample'))->toBeFalse();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Unit/ActiveAppsCacheTest.php`
Expected: FAIL — `Class "AppCore\Contracts\PluginState" not found`.

- [ ] **Step 3: Implementar**

`packages/contracts/src/PluginState.php`:

```php
<?php

namespace AppCore\Contracts;

interface PluginState
{
    public function isActive(string $slug): bool;

    public function accessPermissionFor(string $slug): ?string;
}
```

`packages/core/src/Support/ActiveAppsCache.php`:

```php
<?php

namespace AppCore\Core\Support;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Contracts\PluginState;
use AppCore\Core\Models\PluginApp;
use Throwable;

class ActiveAppsCache implements PluginState
{
    public function __construct(
        private readonly string $cacheFile,
    ) {
    }

    public function isActive(string $slug): bool
    {
        return array_key_exists($slug, $this->read());
    }

    public function accessPermissionFor(string $slug): ?string
    {
        return $this->read()[$slug] ?? null;
    }

    /** @return array<int, string> slugs activos */
    public function rebuild(): array
    {
        $map = [];
        $appsPath = rtrim((string) config('plugin-core.apps_path'), '/');

        foreach (PluginApp::where('status', PluginApp::STATUS_ACTIVE)->pluck('slug') as $slug) {
            try {
                $manifest = Manifest::fromFile($appsPath.'/'.$slug.'/app.json');
            } catch (InvalidManifest) {
                continue; // app activa sin manifest válido: no se cachea; apps:verify (Fase 2) lo reporta
            }
            $map[$slug] = $manifest->accessPermission();
        }

        $dir = dirname($this->cacheFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tmp = $this->cacheFile.'.'.uniqid('', true).'.tmp';
        file_put_contents($tmp, "<?php\n\nreturn ".var_export($map, true).";\n");
        rename($tmp, $this->cacheFile);

        return array_keys($map);
    }

    public function cacheFile(): string
    {
        return $this->cacheFile;
    }

    /** @return array<string, string> slug => permiso de acceso */
    private function read(): array
    {
        try {
            $data = is_file($this->cacheFile) ? require $this->cacheFile : [];
        } catch (Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }
}
```

En `CoreServiceProvider::register()`, añadir tras `mergeConfigFrom`:

```php
$this->app->singleton(ActiveAppsCache::class, fn ($app) => new ActiveAppsCache(
    (string) $app['config']->get('plugin-core.cache_file')
));
$this->app->alias(ActiveAppsCache::class, PluginState::class);
```

(con los `use` correspondientes: `AppCore\Contracts\PluginState;` y `AppCore\Core\Support\ActiveAppsCache;`)

`tests/TestCase.php` — reemplazar por:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
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
```

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Unit/ActiveAppsCacheTest.php && ./vendor/bin/pest`
Expected: PASS (suite completa sigue verde — el `setUp` de TestCase no debe romper los tests de Breeze).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: PluginState con cache de apps activas en archivo"
```

---

### Task 6: spatie/laravel-permission + roles + Gate super-admin + aliases de middleware

**Files:**
- Modify: `packages/core/composer.json` (spatie/laravel-permission)
- Modify: `bootstrap/app.php` (aliases `role`, `permission`)
- Modify: `app/Models/User.php` (`HasRoles`)
- Create: `database/seeders/RolesSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `packages/core/src/CoreServiceProvider.php` (Gate::before)
- Modify: `tests/Pest.php` (helpers `adminUser`, `plainUser`, `userWithPermission`)
- Test: `tests/Feature/RolesTest.php`

**Interfaces:**
- Consumes: Tasks 1-5.
- Produces: roles `super-admin`, `editor`, `usuario` (seed); `adminUser(): User` (super-admin), `plainUser(): User`, `userWithPermission(string $perm): User` en `tests/Pest.php`; aliases de middleware `role` y `permission`; Gate global: `super-admin` → cualquier habilidad = `true`.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Feature/RolesTest.php
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('el seeder crea los tres roles base', function () {
    $this->seed(\Database\Seeders\RolesSeeder::class);

    expect(Role::pluck('name')->all())->toEqualCanonicalizing(['super-admin', 'editor', 'usuario']);
});

test('super-admin pasa cualquier permiso via gate before', function () {
    expect(adminUser()->can('una.permision.cualquiera'))->toBeTrue();
});

test('usuario sin rol no tiene permisos', function () {
    expect(plainUser()->can('notes.access'))->toBeFalse();
});

test('userWithPermission otorga el permiso exacto', function () {
    $user = userWithPermission('notes.access');

    expect($user->can('notes.access'))->toBeTrue()
        ->and($user->can('notes.other'))->toBeFalse()
        ->and(Permission::where('name', 'notes.access')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/RolesTest.php`
Expected: FAIL — función `adminUser()` no definida / clase Role no encontrada (spatie no instalado).

- [ ] **Step 3: Implementar**

En `packages/core/composer.json`, añadir a `require`: `"spatie/laravel-permission": "^6.0"`.
Run: `composer update --no-interaction`

`bootstrap/app.php` — encadenar `withMiddleware` (mantener el resto del archivo):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

`app/Models/User.php` — en la clase añadir `use Spatie\Permission\Traits\HasRoles;` y el trait `use HasRoles;`.

`database/seeders/RolesSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['super-admin', 'editor', 'usuario'] as $role) {
            Role::findOrCreate($role);
        }
    }
}
```

`database/seeders/DatabaseSeeder.php` — dentro de `run()` añadir:

```php
$this->call(RolesSeeder::class);
```

`CoreServiceProvider::boot()` — añadir al inicio del método:

```php
\Illuminate\Support\Facades\Gate::before(
    fn ($user, $ability) => $user->hasRole('super-admin') ? true : null
);
```

`tests/Pest.php` — reemplazar por:

```php
<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

function adminUser(): User
{
    (new RolesSeeder)->run();
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    return $user;
}

function plainUser(): User
{
    return User::factory()->create();
}

function userWithPermission(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

    return $user;
}
```

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/RolesTest.php && ./vendor/bin/pest`
Expected: PASS (la suite completa, incluidos los tests de Breeze).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: roles spatie, gate super-admin y aliases de middleware"
```

---

### Task 7: `PluginManager` — discover, sync, manifestFor

**Files:**
- Create: `packages/core/src/Exceptions/AppNotFound.php`
- Create: `packages/core/src/PluginManager.php`
- Modify: `packages/core/src/CoreServiceProvider.php` (binding singleton)
- Test: `tests/Feature/PluginManagerTest.php` (aparece creciendo por tareas 7-10)

**Interfaces:**
- Consumes: `Manifest`, `PluginApp`, `ActiveAppsCache`.
- Produces: `AppCore\Core\PluginManager` con `discover(): Collection<string, Manifest>`, `sync(): void`, `manifestFor(string $slug): Manifest` (lanza `AppNotFound` si no existe o el slug es inválido). Binding singleton. Las tareas siguientes añaden `activate/deactivate/uninstall/rowsWithStatus` a esta clase.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Feature/PluginManagerTest.php
use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;

function use_fixture_apps_path(): void
{
    config()->set('plugin-core.apps_path', base_path('tests/Fixtures/apps'));
}

test('descubre apps por su app.json en disco', function () {
    use_fixture_apps_path();

    $found = app(PluginManager::class)->discover();

    expect($found->keys()->all())->toEqualCanonicalizing(
        ['sample', 'sample-broken', 'sample-req-laravel', 'sample-req-app']
    )->and($found['sample'])->toBeInstanceOf(Manifest::class);
});

test('sync crea o actualiza filas sin tocar el estado', function () {
    use_fixture_apps_path();
    PluginApp::create(['slug' => 'sample', 'name' => 'Viejo', 'version' => '0.9.0', 'status' => 'active']);

    app(PluginManager::class)->sync();

    $row = PluginApp::where('slug', 'sample')->first();
    expect($row->name)->toBe('Sample App')
        ->and($row->version)->toBe('1.0.0')
        ->and($row->status)->toBe('active') // el estado existente se conserva
        ->and(PluginApp::where('slug', 'sample-broken')->exists())->toBeTrue();
});

test('manifestFor devuelve el manifest', function () {
    use_fixture_apps_path();

    expect(app(PluginManager::class)->manifestFor('sample')->slug())->toBe('sample');
});

test('manifestFor lanza AppNotFound si no existe', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->manifestFor('no-existe');
})->throws(AppNotFound::class, 'App "no-existe" no encontrada');

test('manifestFor rechaza slugs con intento de path traversal', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->manifestFor('../../.env');
})->throws(AppNotFound::class);
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: FAIL — `Class "AppCore\Core\PluginManager" not found`.

- [ ] **Step 3: Implementar**

`packages/core/src/Exceptions/AppNotFound.php`:

```php
<?php

namespace AppCore\Core\Exceptions;

use RuntimeException;

class AppNotFound extends RuntimeException
{
}
```

`packages/core/src/PluginManager.php` (métodos de esta tarea; tareas siguientes añaden más):

```php
<?php

namespace AppCore\Core;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\Collection;

class PluginManager
{
    public function __construct(
        private readonly ActiveAppsCache $cache,
    ) {
    }

    /** @return Collection<string, Manifest> */
    public function discover(): Collection
    {
        $appsPath = rtrim((string) config('plugin-core.apps_path'), '/');
        $found = collect();

        if (! is_dir($appsPath)) {
            return $found;
        }

        foreach (glob($appsPath.'/*/app.json') ?: [] as $file) {
            try {
                $manifest = Manifest::fromFile($file);
            } catch (InvalidManifest $e) {
                report($e); // manifest roto: no rompe el listado; se ignora
                continue;
            }
            $found->put($manifest->slug(), $manifest);
        }

        return $found;
    }

    public function sync(): void
    {
        foreach ($this->discover() as $manifest) {
            PluginApp::updateOrCreate(
                ['slug' => $manifest->slug()],
                ['name' => $manifest->name(), 'version' => $manifest->version()]
            );
        }
    }

    public function manifestFor(string $slug): Manifest
    {
        // El slug viaja hacia un path de archivos: validar antes de tocar el disco.
        if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug)) {
            throw new AppNotFound("App \"{$slug}\" no encontrada");
        }

        $file = rtrim((string) config('plugin-core.apps_path'), '/').'/'.$slug.'/app.json';

        if (! is_file($file)) {
            throw new AppNotFound("App \"{$slug}\" no encontrada");
        }

        return Manifest::fromFile($file);
    }
}
```

En `CoreServiceProvider::register()`, añadir tras el binding de `ActiveAppsCache`:

```php
$this->app->singleton(PluginManager::class, fn ($app) => new PluginManager(
    $app->make(ActiveAppsCache::class)
));
```

(con `use AppCore\Core\PluginManager;` — está en el mismo namespace, no hace falta import.)

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: PluginManager con discover, sync y manifestFor"
```

---

### Task 8: `PluginManager::activate` (migraciones + seeder + permisos + cache)

**Files:**
- Create: `packages/core/src/Exceptions/ActivationFailed.php`
- Modify: `packages/core/src/PluginManager.php` (`activate` + helpers privados)
- Test: `tests/Feature/PluginManagerTest.php`

**Interfaces:**
- Consumes: `manifestFor` (Task 7), `ActiveAppsCache::rebuild` (Task 5), spatie (Task 6), fixtures `sample`/`sample-req-laravel`/`sample-req-app` (Task 4).
- Produces: `PluginManager::activate(string $slug): void` — lanza `AppCore\Core\Exceptions\ActivationFailed` si fallan requisitos (estado intacto) o migraciones/seeder (estado pasa a `error` + `last_error`, cache reconstruido). Al éxito: fila `active` con `activated_at`, permisos spatie creados, cache actualizado.

- [ ] **Step 1: Test fallido**

Añadir a `tests/Feature/PluginManagerTest.php`:

```php
use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Support\ActiveAppsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

test('activar ejecuta migraciones, crea permisos, marca activa y actualiza el cache', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    $manager->activate('sample');

    expect(Schema::hasTable('sample_items'))->toBeTrue()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeTrue();

    $row = PluginApp::where('slug', 'sample')->first();
    expect($row->status)->toBe(PluginApp::STATUS_ACTIVE)
        ->and($row->activated_at)->not->toBeNull()
        ->and($row->last_error)->toBeNull();

    $cache = app(ActiveAppsCache::class);
    expect($cache->isActive('sample'))->toBeTrue()
        ->and($cache->accessPermissionFor('sample'))->toBe('sample.access');
});

test('no activa si la version de Laravel no cumple el requisito', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->activate('sample-req-laravel');
})->throws(ActivationFailed::class, 'Requiere Laravel ^99.0');

test('no activa si una app requerida no esta activa', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->activate('sample-req-app');
})->throws(ActivationFailed::class, "Requiere la app \"sample\" activa");

test('los requisitos fallidos no cambian el estado', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    try {
        $manager->activate('sample-req-app');
    } catch (ActivationFailed) {
    }

    $row = PluginApp::where('slug', 'sample-req-app')->first();
    expect($row->status)->toBe(PluginApp::STATUS_INACTIVE)
        ->and(app(ActiveAppsCache::class)->isActive('sample-req-app'))->toBeFalse();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: FAIL — `Call to undefined method [PluginManager::activate()]`.

- [ ] **Step 3: Implementar**

`packages/core/src/Exceptions/ActivationFailed.php`:

```php
<?php

namespace AppCore\Core\Exceptions;

use RuntimeException;

class ActivationFailed extends RuntimeException
{
    public function __construct(
        string $slug,
        string $reason,
        public readonly ?\Throwable $previous = null,
    ) {
        parent::__construct("No se pudo activar \"{$slug}\": {$reason}", 0, $previous);
    }
}
```

Añadir a `PluginManager`:

```php
use AppCore\Core\Exceptions\ActivationFailed;
use Composer\Semver\Semver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Permission;

public function activate(string $slug): void
{
    $manifest = $this->manifestFor($slug);
    $this->assertRequirements($manifest);

    $row = PluginApp::updateOrCreate(
        ['slug' => $slug],
        ['name' => $manifest->name(), 'version' => $manifest->version(), 'last_error' => null]
    );

    try {
        Artisan::call('migrate', [
            '--path' => $this->baseRelative($manifest->migrationsPath(), $slug),
            '--force' => true,
        ]);

        if ($seeder = $manifest->seeder()) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        foreach ($manifest->permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    } catch (\Throwable $e) {
        $row->forceFill([
            'status' => PluginApp::STATUS_ERROR,
            'last_error' => $e->getMessage(),
        ])->save();
        $this->cache->rebuild();

        throw new ActivationFailed($slug, $e->getMessage(), $e);
    }

    $row->forceFill([
        'status' => PluginApp::STATUS_ACTIVE,
        'activated_at' => now(),
        'last_error' => null,
    ])->save();

    $this->cache->rebuild();
}

private function assertRequirements(Manifest $manifest): void
{
    if ($c = $manifest->phpConstraint()) {
        if (! Semver::satisfies(PHP_VERSION, $c)) {
            throw new ActivationFailed($manifest->slug(), "Requiere PHP {$c}, actual ".PHP_VERSION);
        }
    }

    if ($c = $manifest->laravelConstraint()) {
        if (! Semver::satisfies(app()->version(), $c)) {
            throw new ActivationFailed($manifest->slug(), "Requiere Laravel {$c}, actual ".app()->version());
        }
    }

    foreach ($manifest->requiredApps() as $dependency) {
        if (PluginApp::where('slug', $dependency)->value('status') !== PluginApp::STATUS_ACTIVE) {
            throw new ActivationFailed($manifest->slug(), "Requiere la app \"{$dependency}\" activa");
        }
    }
}

private function baseRelative(string $absolutePath, string $slug): string
{
    $base = base_path();
    if (str_starts_with($absolutePath, $base.'/')) {
        return substr($absolutePath, strlen($base) + 1);
    }

    throw new ActivationFailed($slug, "Las apps deben vivir dentro de {$base}; ruta fuera de la raíz: {$absolutePath}");
}
```

Los dos call sites en `activate()` pasan el slug: `$this->baseRelative($manifest->migrationsPath(), $slug)`.

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: activacion de apps con migraciones, permisos y cache"
```

---

### Task 9: Fallo al activar → estado `error` + reintento

**Files:**
- Modify: `packages/core/src/PluginManager.php` (solo si hace falta; la lógica ya está — el test la fija)
- Test: `tests/Feature/PluginManagerTest.php`

**Interfaces:**
- Consumes: `activate` (Task 8), fixture `sample-broken`.
- Produces: contrato de fallo: `status='error'`, `last_error` contiene el mensaje original, la app NO entra al cache, y un `activate()` posterior reintenta desde cero (idempotente).

- [ ] **Step 1: Test fallido**

Añadir a `tests/Feature/PluginManagerTest.php`:

```php
test('migracion rota deja la app en error, fuera del cache, con mensaje reintetable', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    expect(fn () => $manager->activate('sample-broken'))
        ->toThrow(ActivationFailed::class);

    $row = PluginApp::where('slug', 'sample-broken')->first();
    expect($row->status)->toBe(PluginApp::STATUS_ERROR)
        ->and($row->last_error)->toContain('boom')
        ->and(Schema::hasTable('broken_table'))->toBeFalse()
        ->and(app(ActiveAppsCache::class)->isActive('sample-broken'))->toBeFalse();
});

test('reintentar la activacion es posible y vuelve a fallar limpiamente', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    try {
        $manager->activate('sample-broken');
    } catch (ActivationFailed) {
    }
    expect(fn () => $manager->activate('sample-broken'))->toThrow(ActivationFailed::class);

    expect(PluginApp::where('slug', 'sample-broken')->value('status'))->toBe(PluginApp::STATUS_ERROR);
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: FAIL en los dos tests nuevos (hoy el estado tras fallo y el reintento no están fijados por test — si pasan ya, verifica que `last_error` contiene `boom`; si no, aplica el Step 3).

- [ ] **Step 3: Asegurar la implementación**

El comportamiento ya vive en el `catch` de `activate()` (Task 8): `status=error` + `last_error` + `rebuild()` + `throw ActivationFailed`. Si el test de `last_error` falla porque el mensaje viene envuelto, mantener `toContain('boom')` (el mensaje original se preserva vía `$e->getMessage()`).

Verificar además que un `updateOrCreate` previo no deja basura: en `activate`, el `updateOrCreate` inicial pone `last_error => null` y **no** cambia `status` (queda como esté hasta que termine el try) — eso ya está así.

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php && ./vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: manejo de error en activacion con reintento"
```

---

### Task 10: `deactivate` y `uninstall`

**Files:**
- Modify: `packages/core/src/PluginManager.php` (`deactivate`, `uninstall`)
- Test: `tests/Feature/PluginManagerTest.php`

**Interfaces:**
- Consumes: Tasks 5-9.
- Produces: `PluginManager::deactivate(string $slug): void` (→ `inactive`, elimina permisos spatie + `forgetCachedPermissions`, cache reconstruido, **datos intactos**); `PluginManager::uninstall(string $slug): void` (si estaba activa la desactiva primero → rollback de migraciones por `--path`+`--step` → borra fila + cache). Ambos lanzan `AppNotFound` si no hay fila.

- [ ] **Step 1: Test fallido**

Añadir a `tests/Feature/PluginManagerTest.php`:

```php
test('desactivar oculta la app y conserva los datos', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');
    DB::table('sample_items')->insert(['name' => 'registro vivo', 'created_at' => now(), 'updated_at' => now()]);

    $manager->deactivate('sample');

    expect(Schema::hasTable('sample_items'))->toBeTrue()
        ->and(DB::table('sample_items')->count())->toBe(1)
        ->and(PluginApp::where('slug', 'sample')->value('status'))->toBe(PluginApp::STATUS_INACTIVE)
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeFalse()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeFalse();
});

test('desactivar una app no instalada lanza AppNotFound', function () {
    use_fixture_apps_path();

    app(PluginManager::class)->deactivate('no-existe');
})->throws(AppNotFound::class);

test('reactivar despues de desactivar restaura permisos', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);

    $manager->activate('sample');
    $manager->deactivate('sample');
    $manager->activate('sample');

    expect(Permission::where('name', 'sample.access')->exists())->toBeTrue()
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeTrue();
});

test('desinstalar revierte migraciones, borra la fila y limpia el cache', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');

    $manager->uninstall('sample');

    expect(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(PluginApp::where('slug', 'sample')->exists())->toBeFalse()
        ->and(app(ActiveAppsCache::class)->isActive('sample'))->toBeFalse()
        ->and(Permission::where('name', 'sample.access')->exists())->toBeFalse();
});

test('desinstalar una app activa primero la desactiva', function () {
    use_fixture_apps_path();
    $manager = app(PluginManager::class);
    $manager->activate('sample');

    $manager->uninstall('sample');

    expect(Schema::hasTable('sample_items'))->toBeFalse()
        ->and(PluginApp::where('slug', 'sample')->exists())->toBeFalse();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php`
Expected: FAIL — `Call to undefined method [PluginManager::deactivate()]`.

- [ ] **Step 3: Implementar**

Añadir a `PluginManager`:

```php
public function deactivate(string $slug): void
{
    $manifest = $this->manifestFor($slug);
    $row = PluginApp::where('slug', $slug)->firstOrFail();

    foreach ($manifest->permissions() as $permission) {
        Permission::where('name', $permission)->where('guard_name', 'web')->delete();
    }
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $row->forceFill(['status' => PluginApp::STATUS_INACTIVE])->save();

    $this->cache->rebuild();
}

public function uninstall(string $slug): void
{
    $manifest = $this->manifestFor($slug);
    $row = PluginApp::where('slug', $slug)->firstOrFail();

    if ($row->status === PluginApp::STATUS_ACTIVE) {
        $this->deactivate($slug);
    }

    Artisan::call('migrate:rollback', [
        '--path' => $this->baseRelative($manifest->migrationsPath(), $slug),
        '--step' => 1000, // batches recientes filtrados por --path: solo caen migraciones de ESTA app
        '--force' => true,
    ]);

    $row->delete(); // slug, config, last_error y activated_at mueren con la fila

    $this->cache->rebuild();
}
```

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/PluginManagerTest.php && ./vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: desactivar conserva datos; desinstalar revierte migraciones"
```

---

### Task 11: `PluginServiceProvider` base (gating + rutas) + app de ejemplo `notes`

**Files:**
- Create: `packages/contracts/src/PluginServiceProvider.php`
- Create: `apps/notes/{composer.json,app.json,src/NotesServiceProvider.php,routes/web.php,database/migrations/2026_10_13_000001_create_notes_table.php}`
- Modify: `composer.json` (repo `apps/*` + require `appcore/notes`)
- Modify: `tests/Pest.php` (helper `bootNotesProvider`)
- Test: `tests/Unit/PluginServiceProviderTest.php`

**Interfaces:**
- Consumes: `PluginState` (Task 5), `PluginManager::activate` (Task 8), `config('plugin-core.apps_path')`.
- Produces: `AppCore\Contracts\PluginServiceProvider` (abstract): `slug(): string` (abstract), `bootPlugin(): void` (override), `routes(string $relativeFile): void` (registra con middleware `web`, `auth`, `app.access:{slug}`, prefijo `apps/{slug}` y nombre `apps.{slug}.`), gating: inactiva → `boot()` no registra nada; excepción en `bootPlugin()` → `report()` sin tumbar la app. App real `apps/notes` con ruta nombrada `apps.notes.hello` → `GET /apps/notes/hello`. Helper de test `bootNotesProvider()`.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Unit/PluginServiceProviderTest.php
use AppCore\Notes\NotesServiceProvider;
use AppCore\Core\PluginManager;
use Illuminate\Support\Facades\Route;

test('provider de una app inactiva no registra rutas', function () {
    $provider = new NotesServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    expect(Route::has('apps.notes.hello'))->toBeFalse();
});

test('provider de una app activa registra ruta con prefijo, nombre y middleware', function () {
    app(PluginManager::class)->activate('notes');

    $provider = new NotesServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    expect(Route::has('apps.notes.hello'))->toBeTrue();

    $route = Route::getRoutes()->getByName('apps.notes.hello');
    expect($route->uri())->toBe('apps/notes/hello')
        ->and($route->middleware())->toContain('app.access:notes')
        ->and($route->middleware())->toContain('web')
        ->and($route->middleware())->toContain('auth');
});

test('un bootPlugin que revienta no propaga la excepcion', function () {
    $provider = new class($this->app) extends \AppCore\Contracts\PluginServiceProvider {
        protected function slug(): string
        {
            return 'no-existe';
        }

        protected function bootPlugin(): void
        {
            throw new \RuntimeException('fallo deliberado');
        }
    };

    $provider->register();
    $provider->boot(); // no debe lanzar

    expect(true)->toBeTrue();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Unit/PluginServiceProviderTest.php`
Expected: FAIL — `Class "AppCore\Contracts\PluginServiceProvider" not found`.

- [ ] **Step 3: Implementar `PluginServiceProvider`**

`packages/contracts/src/PluginServiceProvider.php`:

```php
<?php

namespace AppCore\Contracts;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class PluginServiceProvider extends ServiceProvider
{
    abstract protected function slug(): string;

    protected function bootPlugin(): void
    {
        //
    }

    public function boot(): void
    {
        if (! app(PluginState::class)->isActive($this->slug())) {
            return; // app inactiva: no registra absolutamente nada
        }

        try {
            $this->bootPlugin();
        } catch (\Throwable $e) {
            report($e); // una app rota nunca tumba el sitio
        }
    }

    protected function routes(string $relativeFile): void
    {
        $file = $this->packagePath($relativeFile);
        if (! is_file($file)) {
            report(new \RuntimeException("routes(): no existe {$file}"));
            return;
        }

        Route::middleware(['web', 'auth', 'app.access:'.$this->slug()])
            ->prefix('apps/'.$this->slug())
            ->name('apps.'.$this->slug().'.')
            ->group($file);
    }

    protected function packagePath(string $relative): string
    {
        return rtrim((string) config('plugin-core.apps_path'), '/')
            .'/'.$this->slug().'/'.ltrim($relative, '/');
    }
}
```

- [ ] **Step 4: Implementar la app `notes`**

`composer.json` raíz — en `repositories` añadir:

```json
{ "type": "path", "url": "apps/*", "options": { "symlink": true } }
```

y en `require`: `"appcore/notes": "^1.0"`.

`apps/notes/composer.json`:

```json
{
    "name": "appcore/notes",
    "description": "App de ejemplo: notas",
    "type": "library",
    "version": "1.0.0",
    "require": {
        "php": ">=8.3",
        "appcore/contracts": "^1.0"
    },
    "autoload": { "psr-4": { "AppCore\\Notes\\": "src/" } },
    "extra": { "laravel": { "providers": ["AppCore\\Notes\\NotesServiceProvider"] } }
}
```

`apps/notes/app.json`:

```json
{
    "slug": "notes",
    "name": "Notas",
    "version": "1.0.0",
    "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
    "permissions": ["notes.access"],
    "config": {},
    "migrations": "database/migrations"
}
```

`apps/notes/src/NotesServiceProvider.php`:

```php
<?php

namespace AppCore\Notes;

use AppCore\Contracts\PluginServiceProvider;

class NotesServiceProvider extends PluginServiceProvider
{
    protected function slug(): string
    {
        return 'notes';
    }

    protected function bootPlugin(): void
    {
        $this->routes('routes/web.php');
    }
}
```

`apps/notes/routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello', fn () => response('notes-hello'))->name('hello');
```

`apps/notes/database/migrations/2026_10_13_000001_create_notes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
```

Run: `composer update --no-interaction`

En `tests/Pest.php`, añadir:

```php
function bootNotesProvider(): void
{
    // Simula el arranque del provider en el SIGUIENTE request (en producción,
    // cada request vuelve a hacer boot de los providers con el cache actual).
    $provider = new \AppCore\Notes\NotesServiceProvider(app());
    $provider->register();
    $provider->boot();
}
```

- [ ] **Step 5: Ver pasar**

Run: `./vendor/bin/pest tests/Unit/PluginServiceProviderTest.php && ./vendor/bin/pest`
Expected: PASS (los tests con `activate('notes')` también ejecutan la migración de notes; `RefreshDatabase` la limpia).

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: PluginServiceProvider con gating y app de ejemplo notes"
```

---

### Task 12: Middleware `app.access` + gating HTTP (200 / 403 / 404)

**Files:**
- Create: `packages/core/src/Http/Middleware/AppAccess.php`
- Modify: `bootstrap/app.php` (alias `app.access`)
- Test: `tests/Feature/RouteGatingTest.php`

**Interfaces:**
- Consumes: `PluginState` (isActive/accessPermissionFor), rutas de `notes` (Task 11), roles/permisos (Task 6).
- Produces: alias de middleware `app.access` → `AppCore\Core\Http\Middleware\AppAccess` con firma `handle($request, $next, string $slug)`. Comportamiento HTTP: inactiva → 404; activa sin permiso → 403; activa con permiso → 200; invitado → redirect a login; super-admin → pasa sin permiso explícito.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Feature/RouteGatingTest.php
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
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/RouteGatingTest.php`
Expected: FAIL — alias `app.access` no definido (exception al ejecutar la ruta) o 500.

- [ ] **Step 3: Implementar**

`packages/core/src/Http/Middleware/AppAccess.php`:

```php
<?php

namespace AppCore\Core\Http\Middleware;

use AppCore\Contracts\PluginState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppAccess
{
    public function __construct(
        private readonly PluginState $state,
    ) {
    }

    public function handle(Request $request, Closure $next, string $slug): Response
    {
        // 1) Si la app no está activa, ni siquiera existe (404, no "denegado").
        abort_unless($this->state->isActive($slug), 404);

        // 2) Activa: exige el permiso de acceso (super-admin pasa por Gate::before).
        $access = $this->state->accessPermissionFor($slug) ?? "{$slug}.access";
        abort_unless($request->user()?->can($access) ?? false, 403);

        return $next($request);
    }
}
```

`bootstrap/app.php` — en el `alias([...])` de `withMiddleware` añadir:

```php
'app.access' => \AppCore\Core\Http\Middleware\AppAccess::class,
```

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/RouteGatingTest.php && ./vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: middleware app.access con gating 200/403/404"
```

---

### Task 13: Panel admin `/admin/apps` (rutas, vista, componente Livewire, nav)

**Files:**
- Create: `packages/core/routes/admin.php`
- Create: `packages/core/src/Http/Livewire/Admin/AppsPage.php`
- Create: `packages/core/resources/views/admin/apps.blade.php`
- Modify: `packages/core/src/CoreServiceProvider.php` (loadRoutesFrom + Livewire::component)
- Modify: `resources/views/layouts/app.blade.php` (link "Apps" solo super-admin)
- Test: `tests/Feature/Admin/AppsPageTest.php`

**Interfaces:**
- Consumes: `PluginManager` completo (Tasks 7-10), aliases `role` (Task 6), `app.access` (Task 12), layout Breeze `layouts.app`.
- Produces: `GET /admin/apps` (nombre `admin.apps.index`, middleware `web|auth|role:super-admin`), componente Livewire registrado como `admin.apps` con props `apps: array`, `flash: ?string`, `flashType: string`, `confirming: string`, `confirmSlug: string` y acciones `activate($slug)`, `deactivate($slug)`, `confirmUninstall($slug)`, `cancelUninstall()`, `uninstall($slug)`. `PluginManager::rowsWithStatus(): array` con filas `['slug','name','version','status','last_error']`.

- [ ] **Step 1: Test fallido**

```php
<?php
// tests/Feature/Admin/AppsPageTest.php
use AppCore\Core\Http\Livewire\Admin\AppsPage;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;
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
    \Illuminate\Support\Facades\DB::table('notes')
        ->insert(['title' => 'viva', 'created_at' => now(), 'updated_at' => now()]);

    Livewire::test(AppsPage::class)
        ->call('deactivate', 'notes')
        ->assertSet('flashType', 'success');

    expect(\Illuminate\Support\Facades\Schema::hasTable('notes'))->toBeTrue()
        ->and(\Illuminate\Support\Facades\DB::table('notes')->count())->toBe(1)
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

    expect(PluginApp::where('slug', 'notes')->exists())->toBeFalse()
        ->and(\Illuminate\Support\Facades\Schema::hasTable('notes'))->toBeFalse();
});

test('uninstall sin confirmacion no borra nada', function () {
    app(PluginManager::class)->activate('notes');

    Livewire::test(AppsPage::class)->call('uninstall', 'notes');

    expect(PluginApp::where('slug', 'notes')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Ver fallar**

Run: `./vendor/bin/pest tests/Feature/Admin/AppsPageTest.php`
Expected: FAIL — 404 en `/admin/apps` (ruta no existe).

- [ ] **Step 3: Implementar**

Añadir a `PluginManager`:

```php
/** @return array<int, array{slug: string, name: string, version: string, status: string, last_error: ?string}> */
public function rowsWithStatus(): array
{
    $this->sync();

    $rows = PluginApp::orderBy('slug')->get()->keyBy('slug');

    return $this->discover()
        ->map(function (Manifest $manifest, string $slug) use ($rows) {
            $row = $rows->get($slug);

            return [
                'slug' => $slug,
                'name' => $manifest->name(),
                'version' => $manifest->version(),
                'status' => $row?->status ?? PluginApp::STATUS_INACTIVE,
                'last_error' => $row?->last_error,
            ];
        })
        ->values()
        ->all();
}
```

`packages/core/routes/admin.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/apps', fn () => view('core::admin.apps'))->name('apps.index');
    });
```

`packages/core/src/Http/Livewire/Admin/AppsPage.php`:

```php
<?php

namespace AppCore\Core\Http\Livewire\Admin;

use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\PluginManager;
use Livewire\Component;

class AppsPage extends Component
{
    public array $apps = [];

    public ?string $flash = null;

    public string $flashType = 'success';

    public string $confirming = '';

    public string $confirmSlug = '';

    public function mount(): void
    {
        $this->load();
    }

    public function activate(string $slug): void
    {
        try {
            app(PluginManager::class)->activate($slug);
            $this->setFlash('success', "App \"{$slug}\" activada.");
        } catch (ActivationFailed | AppNotFound $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->load();
    }

    public function deactivate(string $slug): void
    {
        try {
            app(PluginManager::class)->deactivate($slug);
            $this->setFlash('success', "App \"{$slug}\" desactivada.");
        } catch (AppNotFound $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->load();
    }

    public function confirmUninstall(string $slug): void
    {
        $this->confirming = 'uninstall';
        $this->confirmSlug = $slug;
    }

    public function cancelUninstall(): void
    {
        $this->confirming = '';
        $this->confirmSlug = '';
    }

    public function uninstall(string $slug): void
    {
        if ($slug !== $this->confirmSlug) {
            $this->setFlash('error', 'Confirmación requerida para desinstalar.');
            return;
        }

        try {
            app(PluginManager::class)->uninstall($slug);
            $this->setFlash('success', "App \"{$slug}\" desinstalada.");
        } catch (ActivationFailed | AppNotFound $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->cancelUninstall();
        $this->load();
    }

    private function load(): void
    {
        $this->apps = app(PluginManager::class)->rowsWithStatus();
    }

    private function setFlash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flash = $message;
        $this->dispatch('toast', type: $type, message: $message);
    }
}
```

`packages/core/resources/views/admin/apps.blade.php` (si la variante de Breeze no usa `@extends('layouts.app')`, adaptar a la convención de layout real de `resources/views/`, manteniendo el contenido):

```blade
@extends('layouts.app')
@section('content')
    <div class="py-6" x-data="{ toasts: [] }" x-on:toast.window="toasts.push({ id: Date.now(), ...$event.detail })">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Apps</h1>
            <span class="text-sm text-gray-500">Instaladas en <code>apps/</code></span>
        </div>

        @if ($flash)
            <div @class([
                'mb-4 rounded px-4 py-3 text-sm',
                'bg-green-100 text-green-800' => $flashType === 'success',
                'bg-red-100 text-red-800' => $flashType === 'error',
            ])>{{ $flash }}</div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium">App</th>
                        <th class="px-4 py-2 text-left font-medium">Versión</th>
                        <th class="px-4 py-2 text-left font-medium">Estado</th>
                        <th class="px-4 py-2 text-left font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($apps as $app)
                        <tr>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ $app['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $app['slug'] }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $app['version'] }}</td>
                            <td class="px-4 py-2">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800' => $app['status'] === 'active',
                                    'bg-gray-100 text-gray-700' => $app['status'] === 'inactive',
                                    'bg-red-100 text-red-800' => $app['status'] === 'error',
                                ])>{{ $app['status'] }}</span>
                                @if ($app['last_error'])
                                    <div class="mt-1 max-w-md text-xs text-red-600">{{ $app['last_error'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-2">
                                    @if ($app['status'] !== 'active')
                                        <button wire:click="activate('{{ $app['slug'] }}')"
                                            class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700">
                                            {{ $app['status'] === 'error' ? 'Reintentar' : 'Activar' }}
                                        </button>
                                    @else
                                        <button wire:click="deactivate('{{ $app['slug'] }}')"
                                            class="rounded bg-gray-200 px-3 py-1 text-xs hover:bg-gray-300">
                                            Desactivar
                                        </button>
                                    @endif

                                    <button wire:click="confirmUninstall('{{ $app['slug'] }}')"
                                        class="rounded bg-red-100 px-3 py-1 text-xs text-red-700 hover:bg-red-200">
                                        Desinstalar
                                    </button>
                                </div>

                                @if ($confirming === 'uninstall' && $confirmSlug === $app['slug'])
                                    <div class="mt-2 rounded border border-red-300 bg-red-50 p-2 text-xs">
                                        Escribe <strong>{{ $app['slug'] }}</strong> para confirmar la desinstalación
                                        (se eliminarán sus tablas y datos):
                                        <input type="text" class="mt-1 w-full rounded border px-2 py-1"
                                            wire:model.live="confirmSlug">
                                        <div class="mt-2 flex gap-2">
                                            <button wire:click="uninstall('{{ $app['slug'] }}')"
                                                class="rounded bg-red-600 px-2 py-1 text-white">Sí, desinstalar</button>
                                            <button wire:click="cancelUninstall"
                                                class="rounded bg-gray-200 px-2 py-1">Cancelar</button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay apps en <code>apps/</code></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
```

`CoreServiceProvider::boot()` — añadir:

```php
$this->loadRoutesFrom(__DIR__.'/../routes/admin.php');

if (class_exists(\Livewire\Livewire::class)) {
    \Livewire\Livewire::component('admin.apps', \AppCore\Core\Http\Livewire\Admin\AppsPage::class);
}
```

`resources/views/layouts/app.blade.php` — localizar la navegación (`grep -n "Dashboard" resources/views/layouts/app.blade.php`) y añadir, junto al link de Dashboard:

```blade
@auth
    @role('super-admin')
        <x-nav-link :href="route('admin.apps.index')" :active="request()->routeIs('admin.apps.index')">
            Apps
        </x-nav-link>
    @endrole
@endauth
```

(En la pila Livewire de Breeze la navegación suele ser `<x-nav-link>` dentro de `layouts/app.blade.php`; si tu variante usa otro componente de link, adaptar la etiqueta manteniendo los mismos guard `@auth/@role`.)

- [ ] **Step 4: Ver pasar**

Run: `./vendor/bin/pest tests/Feature/Admin/AppsPageTest.php && ./vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Verificación manual del flujo (UI real)**

```bash
php artisan migrate --seed
php artisan serve
```

Crear un super-admin, p. ej. con `php artisan tinker`:

```php
use App\Models\User;
$u = User::factory()->create();
$u->assignRole('super-admin');
```

Entrar a `/login`, abrir **Apps** en el sidebar, activar `notes` y comprobar: badge `active` en la tabla, y `GET /apps/notes/hello` responde 403 sin permiso / 200 si el usuario tiene `notes.access`. Cerrar y volver a entrar si hace falta para refrescar permisos.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: panel admin /admin/apps con activar, desactivar y desinstalar"
```

---

### Task 14: Aceptación de Fase 1 + documentación

**Files:**
- Create: `docs/plugin-system.md`
- Test: ejecución de toda la suite + checklist de aceptación

**Interfaces:**
- Consumes: todo lo anterior.
- Produces:** Fase 1 entregada y verificada contra los criterios del spec §11.

- [ ] **Step 1: Suite completa**

Run: `./vendor/bin/pest`
Expected: PASS, 0 fallos. Si hay fallos, arreglar la causa (no desactivar tests).

- [ ] **Step 2: Checklist de aceptación (spec §11, lo aplicable a Fase 1)**

Ejecutar y comprobar cada punto:

```bash
# 1. Auth de Breeze funcionando
./vendor/bin/pest tests/Feature/AuthenticationTest.php   # PASS (login/logout/registro)

# 2. notes descubierta y aparece inactiva en el listado
# (si el paso manual del Task 13 la dejó activa, desactivarla primero:)
php artisan tinker --execute="try { app(\AppCore\Core\PluginManager::class)->deactivate('notes'); } catch (\Throwable) {}"
php artisan tinker --execute="echo json_encode(app(\AppCore\Core\PluginManager::class)->rowsWithStatus(), JSON_PRETTY_PRINT);"
# → debe incluir "slug": "notes", "status": "inactive"

# 3. Activar → migraciones + permisos + ruta 200 + link en sidebar
php artisan tinker --execute="app(\AppCore\Core\PluginManager::class)->activate('notes'); echo 'ok';"
# → tabla notes existe, permiso notes.access existe, fila active, cache con notes
# → con un usuario con notes.access: GET /apps/notes/hello = 200
# → link "Apps" visible en sidebar para super-admin (flujo manual del Task 13 Step 5)

# 4. Desactivar → 404 y datos intactos; 5. sin permiso → 403
./vendor/bin/pest tests/Feature/RouteGatingTest.php      # PASS cubre 4 y 5

# 6. Migración rota → status error, sin tumbar el sitio, con reintento
./vendor/bin/pest --filter="migracion rota"              # PASS

# 7. Suite Pest completa en verde
./vendor/bin/pest                                       # PASS
```

Criterio 3 parcial: la **entrada del sidebar para cada app activa** (API de menús) es Fase 2; en Fase 1 el link "Apps" del admin y las rutas bajo `/apps/{slug}` quedan verificados.

- [ ] **Step 3: Documentación para crear una app**

Crear `docs/plugin-system.md` con este contenido:

```markdown
# Sistema de apps del core (Fase 1)

## Cómo crear una app

1. Crea la carpeta `apps/{slug}/` (slug: minúsculas, números y guiones).
2. Añade `apps/{slug}/app.json`:

   {
     "slug": "mi-app",
     "name": "Mi App",
     "version": "1.0.0",
     "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
     "permissions": ["mi-app.access"],
     "migrations": "database/migrations"
   }

   Reglas: todos los permisos empiezan por `{slug}.`; `requires.apps` lista apps
   que deben estar activas antes de activar esta.
3. Añade `apps/{slug}/composer.json` con `"require": { "appcore/contracts": "^1.0" }`
   y `extra.laravel.providers` apuntando a tu ServiceProvider (ver `apps/notes/`).
4. Tu ServiceProvider extiende `AppCore\Contracts\PluginServiceProvider`,
   implementa `slug()` y registra rutas con `$this->routes('routes/web.php')`.
5. Ejecuta `composer update` en la raíz (registra el path repository `apps/*`).
6. Activa la app desde **/admin/apps** (o `PluginManager::activate('mi-app')`).

## Ciclo de vida

- **Activar**: valida manifest (PHP/Laravel/apps requeridas) → corre migraciones y
  seeder → crea permisos → `status=active` → reescribe `bootstrap/cache/active-apps.php`.
  Si algo falla: `status=error` + `last_error`, queda fuera del cache y el botón
  "Reintentar" vuelve a intentarlo.
- **Desactivar**: quita permisos, rutas y menú; NO borra datos.
- **Desinstalar**: pide confirmación (escribir el slug) → rollback de migraciones →
  borra la fila.

## Seguridad

- `/admin/*` requiere rol `super-admin`.
- Rutas de apps: prefijo `/apps/{slug}` + middleware `app.access:{slug}`
  (404 si inactiva, 403 sin permiso).
- Una app con error en el boot solo se oculta a sí misma (report() en el log),
  nunca tumba el sitio.
```

- [ ] **Step 4: Commit final**

```bash
git add -A && git commit -m "docs: guia del sistema de apps y checklist de aceptacion Fase 1"
```

- [ ] **Step 5: Reporte de entrega**

Reportar al usuario: suite verde (número de tests), criterios cumplidos (1-7 del spec §11, con el matiz del criterio 3), y qué queda para Fase 2 (CLI `apps:*`, API de menús, widgets, tipos de contenido, pantalla de configuración, `apps:verify`).
