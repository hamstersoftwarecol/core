<?php

namespace AppCore\Core\Console;

use AppCore\Core\Exceptions\ActivationFailed;
use AppCore\Core\Exceptions\AppNotFound;
use AppCore\Core\PluginManager;
use Illuminate\Console\Command;

class AppsActivateCommand extends Command
{
    protected $signature = 'apps:activate {slug}';

    protected $description = 'Activa una app (migraciones, seeder, permisos y cache)';

    public function handle(PluginManager $manager): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $manager->activate($slug);
        } catch (ActivationFailed|AppNotFound $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("App \"{$slug}\" activada.");

        return self::SUCCESS;
    }
}
