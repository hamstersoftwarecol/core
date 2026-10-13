<?php

namespace AppCore\Core\Console;

use AppCore\Core\PluginManager;
use Illuminate\Console\Command;

class AppsListCommand extends Command
{
    protected $signature = 'apps:list';

    protected $description = 'Lista las apps descubiertas en apps/ con su estado';

    public function handle(PluginManager $manager): int
    {
        $rows = $manager->rowsWithStatus();

        if ($rows === []) {
            $this->info('No hay apps en '.config('plugin-core.apps_path'));

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Nombre', 'Versión', 'Instalada', 'Estado', 'Error'],
            array_map(fn (array $r) => [$r['slug'], $r['name'], $r['version'], $r['installed_version'] ?? '', $r['status'], $r['last_error'] ?? ''], $rows),
        );

        return self::SUCCESS;
    }
}
