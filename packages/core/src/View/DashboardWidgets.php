<?php

namespace AppCore\Core\View;

use AppCore\Contracts\Registry\WidgetRegistry;
use Illuminate\View\Component;
use Livewire\Livewire;

class DashboardWidgets extends Component
{
    /** @var array<int, string> HTML ya renderizado de cada widget que no falló */
    public array $rendered = [];

    public function __construct(WidgetRegistry $widgets)
    {
        foreach ($widgets->forUser(auth()->user()) as $widget) {
            try {
                $this->rendered[] = Livewire::mount($widget->component, [], 'widget-'.md5($widget->component));
            } catch (\Throwable $e) {
                report($e); // widget roto: se oculta, el dashboard sigue
            }
        }
    }

    public function render()
    {
        return view('core::components.dashboard-widgets');
    }
}
