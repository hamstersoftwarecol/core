<?php

namespace AppCore\Core\View;

use AppCore\Contracts\Registry\MenuItem;
use AppCore\Contracts\Registry\MenuRegistry;
use Illuminate\View\Component;

class AppMenu extends Component
{
    /** @var array<int, MenuItem> */
    public array $items;

    public function __construct(MenuRegistry $menu, public bool $responsive = false)
    {
        $this->items = $menu->forUser(auth()->user());
    }

    public function render()
    {
        return view('core::components.app-menu');
    }
}
