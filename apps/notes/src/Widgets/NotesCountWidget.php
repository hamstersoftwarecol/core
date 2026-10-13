<?php

namespace AppCore\Notes\Widgets;

use Livewire\Component;

class NotesCountWidget extends Component
{
    public function render()
    {
        return <<<'BLADE'
            <div>
                <div class="text-sm text-gray-500">Notas</div>
                <div class="text-3xl font-semibold">{{ \Illuminate\Support\Facades\DB::table('notes')->count() }}</div>
            </div>
        BLADE;
    }
}
