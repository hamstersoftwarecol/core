# Sistema de apps del core (Fases 1 y 2)

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

## API del plugin (Fase 2)

Dentro de `bootPlugin()` (ver `apps/notes/src/NotesServiceProvider.php`):

| Helper | Efecto |
|---|---|
| `$this->routes('routes/web.php')` | Rutas bajo `/apps/{slug}`, nombres `apps.{slug}.*` |
| `$this->menu('Notas', route: 'index', icon: ..., order: 20)` | Entrada en el nav; filtrada por app activa + permiso (por defecto `{slug}.access`) |
| `$this->widget(MiWidget::class, permission: ...)` | Componente Livewire en el dashboard; si lanza excepción se oculta y se loguea |
| `$this->contentType('blog.post', label: 'Post')` | Registro en runtime; la tabla `content_types` se siembra desde `content_types` del app.json al activar |
| `$this->permissions([...])` | Verifica prefijo; los permisos se siembran desde app.json |
| `$this->config('clave')` | Valor de `/admin/apps/{slug}/config` o default del manifest |

Otras apps consumen tipos ajenos con `app(AppCore\Contracts\Registry\ContentTypeRegistry::class)->all()`.

Campos nuevos de `app.json`:

```json
"config": { "per_page": { "type": "number", "default": 10, "label": "Notas por página" } },
"content_types": { "notes.note": { "label": "Nota", "table": "notes" } }
```

`config.*.type` ∈ `string|number|boolean`; los tipos de contenido deben empezar por `{slug}.`.

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

## Panel admin

- `/admin/apps` — activar / desactivar / desinstalar / configurar.
- `/admin/apps/{slug}/config` — formulario generado desde `config` del manifest.
- `/admin/roles` — crear/borrar roles y asignar permisos (`super-admin` no editable).
- `/admin/users` — asignar roles a usuarios (no puedes quitarte `super-admin` a ti mismo).

## Comandos útiles

- `php artisan apps:list` — apps descubiertas y estado.
- `php artisan apps:activate {slug}` / `apps:deactivate {slug}`.
- `php artisan apps:verify` — tras cada deploy: revalida apps activas (las que fallan → `error`) y reconstruye `active-apps.php`. Sale con código ≠ 0 si alguna falla.

- `./vendor/bin/pest` — suite completa.
- `php artisan tinker` → `app(\AppCore\Core\PluginManager::class)->rowsWithStatus()`
  — listado con estado real.
