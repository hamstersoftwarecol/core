<?php

namespace AppCore\Core\Console;

use AppCore\Core\PluginManager;
use Illuminate\Console\Command;

class AppsVerifyCommand extends Command
{
    protected $signature = 'apps:verify';

    protected $description = 'Revalida manifests de apps activas y reconstruye active-apps.php';

    public function handle(PluginManager $manager): int
    {
        $report = $manager->verify();

        foreach ($report as $slug => $error) {
            $error === null
                ? $this->line("<info>OK</info>    {$slug}")
                : $this->line("<error>ERROR</error> {$slug}: {$error}");
        }

        $failed = count(array_filter($report));
        $this->info('Cache reconstruida. Activas OK: '.(count($report) - $failed).", con error: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
