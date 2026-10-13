<?php

namespace AppCore\Notes;

use AppCore\Contracts\PluginServiceProvider;
use AppCore\Notes\Widgets\NotesCountWidget;

class NotesServiceProvider extends PluginServiceProvider
{
    protected function slug(): string
    {
        return 'notes';
    }

    protected function bootPlugin(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'notes');
        $this->routes('routes/web.php');
        $this->permissions(['notes.access']);
        $this->menu('Notas', route: 'index', icon: 'document-text', order: 20);
        $this->widget(NotesCountWidget::class);
        $this->contentType('notes.note', label: 'Nota');
    }
}
