<?php

namespace AppCore\Notes;

use AppCore\Contracts\PluginServiceProvider;

class NotesServiceProvider extends PluginServiceProvider
{
    protected function slug(): string
    {
        return 'notes';
    }

    protected function bootPlugin(): void
    {
        $this->routes('routes/web.php');
    }
}
