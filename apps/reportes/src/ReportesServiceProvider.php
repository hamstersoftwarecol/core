<?php

namespace AppCore\Reportes;

use AppCore\Contracts\PluginServiceProvider;
use AppCore\Reportes\Widgets\ContentTotalWidget;

class ReportesServiceProvider extends PluginServiceProvider
{
    protected function slug(): string
    {
        return 'reportes';
    }

    protected function bootPlugin(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'reportes');
        $this->routes('routes/web.php');
        $this->menu('Reportes', route: 'index', icon: 'chart-bar', order: 90);
        $this->widget(ContentTotalWidget::class, order: 90);
    }
}
