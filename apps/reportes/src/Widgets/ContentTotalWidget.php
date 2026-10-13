<?php

namespace AppCore\Reportes\Widgets;

use AppCore\Reportes\ContentStats;
use Livewire\Component;

class ContentTotalWidget extends Component
{
    public function render()
    {
        return view('reportes::widget', ['total' => app(ContentStats::class)->total()]);
    }
}
