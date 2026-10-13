<?php

namespace AppCore\Core\Http\Livewire\Admin;

use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\Models\PluginApp;
use AppCore\Core\PluginManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AppConfigPage extends Component
{
    #[Locked]
    public string $slug;

    #[Locked]
    public array $schema = [];

    public array $values = [];

    public ?string $flash = null;

    public string $flashType = 'success';

    public function mount(string $slug): void
    {
        try {
            $manifest = app(PluginManager::class)->manifestFor($slug);
        } catch (AppNotFound) {
            abort(404);
        }

        $this->slug = $slug;
        $this->schema = $manifest->configSchema();

        $stored = PluginApp::where('slug', $slug)->first()?->config ?? [];
        $this->values = array_merge($manifest->configDefaults(), $stored);
    }

    public function save(): void
    {
        try {
            $this->values = app(PluginManager::class)->saveConfig($this->slug, $this->values);
            $this->flashType = 'success';
            $this->flash = 'Configuración guardada.';
        } catch (\InvalidArgumentException $e) {
            $this->flashType = 'error';
            $this->flash = $e->getMessage();
        } catch (ModelNotFoundException) {
            $this->flashType = 'error';
            $this->flash = 'Activa la app al menos una vez antes de configurarla.';
        }

        $this->dispatch('toast', type: $this->flashType, message: $this->flash);
    }

    public function render()
    {
        return view('core::admin.app-config-form');
    }
}
