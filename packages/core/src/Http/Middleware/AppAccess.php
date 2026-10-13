<?php

namespace AppCore\Core\Http\Middleware;

use AppCore\Contracts\PluginState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppAccess
{
    public function __construct(
        private readonly PluginState $state,
    ) {}

    public function handle(Request $request, Closure $next, string $slug): Response
    {
        // 1) Si la app no está activa, ni siquiera existe (404, no "denegado").
        abort_unless($this->state->isActive($slug), 404);

        // 2) Activa: exige el permiso de acceso (super-admin pasa por Gate::before).
        $access = $this->state->accessPermissionFor($slug) ?? "{$slug}.access";
        abort_unless($request->user()?->can($access) ?? false, 403);

        return $next($request);
    }
}
