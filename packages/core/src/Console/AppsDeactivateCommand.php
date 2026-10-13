<?php

namespace AppCore\Core\Console;

use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\PluginManager;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppsDeactivateCommand extends Command
{
    protected $signature = 'apps:deactivate {slug}';

    protected $description = 'Desactiva una app (conserva sus datos)';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $manager->deactivate($slug);
        } catch (AppNotFound|ModelNotFoundException) {
            $this->error("App \"{$slug}\" no encontrada o nunca activada");

            return self::FAILURE;
        }

        $this->info("App \"{$slug}\" desactivada.");

        return self::SUCCESS;
    }
}
