<?php

namespace AppCore\Core\Http\Livewire\Admin;

use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Exceptions\AppNotFound;
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

    public function render()
    {
        return view('core::admin.apps-list');
    }

    private function setFlash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flash = $message;
        $this->dispatch('toast', type: $type, message: $message);
    }
}
