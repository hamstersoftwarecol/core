<?php

namespace AppCore\Core;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\ContentTypeRecord;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;
use Composer\Semver\Semver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PluginManager
{
    public function __construct(
        private readonly ActiveAppsCache $cache,
    ) {}

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
            // `version` en BD = versión INSTALADA (la fija activate); no se pisa aquí
            // para que apps:verify detecte actualizaciones pendientes.
            PluginApp::firstOrCreate(
                ['slug' => $manifest->slug()],
                ['name' => $manifest->name(), 'version' => $manifest->version()]
            )->forceFill(['name' => $manifest->name()])->save();
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
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach ($manifest->contentTypes() as $type => $definition) {
                ContentTypeRecord::updateOrCreate(
                    ['slug' => $type],
                    [
                        'app_slug' => $slug,
                        'label' => (string) ($definition['label'] ?? $type),
                        'config' => array_diff_key($definition, ['label' => true]),
                    ]
                );
            }
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
            'activated_at' => $row->status === PluginApp::STATUS_ACTIVE && $row->activated_at ? $row->activated_at : now(),
            'last_error' => null,
        ])->save();

        $this->cache->rebuild();
    }

    public function deactivate(string $slug): void
    {
        $manifest = $this->manifestFor($slug);
        $row = PluginApp::where('slug', $slug)->firstOrFail();

        foreach ($manifest->permissions() as $permission) {
            Permission::where('name', $permission)->where('guard_name', 'web')->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        ContentTypeRecord::where('app_slug', $slug)->delete();

        $row->delete(); // slug, config, last_error y activated_at mueren con la fila

        $this->cache->rebuild();
    }

    /**
     * Valida y guarda la configuración contra el schema `config` del manifest.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed> valores normalizados guardados
     */
    public function saveConfig(string $slug, array $values): array
    {
        $manifest = $this->manifestFor($slug);
        $row = PluginApp::where('slug', $slug)->firstOrFail();
        $clean = [];

        foreach ($manifest->configSchema() as $key => $field) {
            $value = $values[$key] ?? $field['default'] ?? null;
            $clean[$key] = match ($field['type']) {
                'number' => is_numeric($value) ? $value + 0 : throw new \InvalidArgumentException("\"{$key}\" debe ser numérico"),
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                default => $value === null ? null : (string) $value,
            };
        }

        $row->forceFill(['config' => $clean])->save();
        app(Support\DatabasePluginConfig::class)->forget($slug);

        return $clean;
    }

    /**
     * Re-aplica activate() a cada app activa (migraciones nuevas, permisos, tipos);
     * las que ya no cumplen pasan a `error`.
     * Reconstruye active-apps.php. Pensado para correr tras cada deploy.
     *
     * @return array<string, ?string> slug => error (null si OK)
     */
    public function verify(): array
    {
        $this->sync();
        $report = [];

        foreach (PluginApp::where('status', PluginApp::STATUS_ACTIVE)->orderBy('slug')->get() as $row) {
            try {
                // activate() es idempotente: corre migraciones pendientes (app actualizada
                // en el deploy), re-siembra permisos/tipos y fija la versión instalada.
                $this->activate($row->slug);
                $report[$row->slug] = null;
            } catch (\Throwable $e) {
                $row->refresh()->forceFill(['status' => PluginApp::STATUS_ERROR, 'last_error' => $e->getMessage()])->save();
                $report[$row->slug] = $e->getMessage();
            }
        }

        $this->cache->rebuild();

        return $report;
    }

    /** @return array<int, array{slug: string, name: string, version: string, installed_version: ?string, status: string, last_error: ?string}> */
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
                    'installed_version' => $row?->status === PluginApp::STATUS_ACTIVE ? $row->version : null,
                    'status' => $row?->status ?? PluginApp::STATUS_INACTIVE,
                    'last_error' => $row?->last_error,
                ];
            })
            ->values()
            ->all();
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
}
