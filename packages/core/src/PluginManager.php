<?php

namespace AppCore\Core;

use AppCore\Contracts\Exceptions\InvalidManifest;
use AppCore\Contracts\Manifest;
use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\Support\ActiveAppsCache;
use Composer\Semver\Semver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

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
}
