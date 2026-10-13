# Sistema de apps del core (Fase 1)

## Cómo crear una app

1. Crea la carpeta `apps/{slug}/` (slug: minúsculas, números y guiones).
2. Añade `apps/{slug}/app.json`:

   ```json
   {
     "slug": "mi-app",
     "name": "Mi App",
     "version": "1.0.0",
     "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
     "permissions": ["mi-app.access"],
     "migrations": "database/migrations"
   }
   ```

   Reglas: todos los permisos empiezan por `{slug}.`; `requires.apps` lista apps
   que deben estar activas antes de activar esta.
3. Añade `apps/{slug}/composer.json` con `"require": { "appcore/contracts": "^1.0" }`
   y `extra.laravel.providers` apuntando a tu ServiceProvider (ver `apps/notes/`).
4. Tu ServiceProvider extiende `AppCore\Contracts\PluginServiceProvider`,
   implementa `slug()` y registra rutas con `$this->routes('routes/web.php')`.
5. Ejecuta `composer update` en la raíz (registra el path repository `apps/*`).
6. Activa la app desde **/admin/apps** (o `PluginManager::activate('mi-app')`).

## Ciclo de vida

- **Activar**: valida manifest (PHP/Laravel/apps requeridas) → corre migraciones y
  seeder → crea permisos → `status=active` → reescribe `bootstrap/cache/active-apps.php`.
  Si algo falla: `status=error` + `last_error`, queda fuera del cache y el botón
  "Reintentar" vuelve a intentarlo.
- **Desactivar**: quita permisos, rutas y menú; NO borra datos.
- **Desinstalar**: pide confirmación (escribir el slug) → rollback de migraciones →
  borra la fila. La lista (`sync`) la vuelve a mostrar como `inactiva` mientras el
  paquete siga en `apps/`.

## Seguridad

- `/admin/*` requiere rol `super-admin`.
- Rutas de apps: prefijo `/apps/{slug}` + middleware `app.access:{slug}`
  (404 si inactiva, 403 sin permiso).
- Una app con error en el boot solo se oculta a sí misma (`report()` en el log),
  nunca tumba el sitio.

## Comandos útiles

- `./vendor/bin/pest` — suite completa.
- `php artisan tinker` → `app(\AppCore\Core\PluginManager::class)->rowsWithStatus()`
  — listado con estado real.
