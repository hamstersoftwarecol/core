# Spec: Core de Laravel con sistema de apps tipo plugin (WordPress-style)

**Fecha:** 2026-10-13
**Estado:** Aprobado en diseño, pendiente de revisión del usuario
**Ruta de brainstorming:** Arquitectónica

## 1. Resumen

Un "core" en Laravel 12 con autenticación Breeze donde el super-admin puede **activar y desactivar apps** (módulos/plugines) desde un panel. Las apps las desarrolla el dueño del proyecto, se empaquetan como paquetes Composer locales, y cada una puede registrar rutas, menú, permisos, widgets de dashboard y tipos de contenido. Modelo análogo a WordPress (instalar/activar plugins), sin multisitio ni marketplace de terceros.

## 2. Decisiones tomadas con el usuario

| Pregunta | Decisión |
|---|---|
| Tipo de producto | Una sola app con plugins (no multisitio, no multi-tenant) |
| Origen de las apps | Desarrolladas por el dueño; activación simple desde el panel (no marketplace en runtime) |
| Tipos de apps previstas | CRUDs de negocio, contenido/blog/páginas, reportes/dashboards, integraciones externas |
| Frontend | Laravel Breeze stack **Livewire** + Flux (tier gratuito) + Tailwind |
| Roles y permisos | `spatie/laravel-permission`, rol `super-admin` gestiona apps |
| Empaquetado | Paquetes Composer locales en `apps/` (path repositories) con manifest `app.json` |
| API del plugin | Rutas, menú y permisos + widgets de dashboard + tipos de contenido + migraciones al activar |
| Enfoque elegido | **A: Core kernel + Composer con gate de alternancia** (descartados nwidart/laravel-modules y microkernel JSON) |

## 3. Stack tecnológico

- Laravel 12 (PHP 8.3+)
- Laravel Breeze, stack Livewire
- Flux UI — tier gratuito (botones, dropdowns, modales...); Pro ($149/proyecto) solo si se necesitan componentes Pro, fuera de alcance
- Tailwind CSS
- `spatie/laravel-permission`
- Pest (tests)
- Composer path repositories para `packages/*` y `apps/*`

## 4. Arquitectura

### 4.1 Estructura del repositorio (monorepo)

```
/
├── app/                    # Laravel estándar + Breeze (scaffold)
├── packages/
│   ├── contracts/          # vendor/contracts — interfaces y DTOs (lo único que importan las apps)
│   └── core/               # vendor/core — PluginManager, admin UI, registries
├── apps/
│   ├── notes/              # app de ejemplo (fixture de integración)
│   └── ...                 # apps reales (blog, reportes, etc.)
├── bootstrap/cache/
│   └── active-apps.php     # cache de apps activas (archivo, no BD)
├── config/plugin-core.php
└── composer.json           # path repositories: packages/*, apps/*
```

**Regla de dependencias:** `apps/*` → `contracts` ← `core`. Una app **nunca** importa `App\*` ni `vendor/core` directamente. Los registros (menú, widgets, contenidos) se exponen como interfaces en `contracts`; el core las enlaza en el contenedor (inversión de dependencias).

### 4.2 Manifest de app (`app.json`)

Cada app trae un `app.json` en su raíz:

```json
{
  "slug": "blog",
  "name": "Blog",
  "version": "1.0.0",
  "requires": { "php": ">=8.3", "laravel": "^12.0", "apps": [] },
  "permissions": ["blog.access", "blog.publish"],
  "config": { "posts_per_page": { "type": "number", "default": 10 } },
  "migrations": "database/migrations",
  "seeder": "Database\\Seeders\\BlogSeeder"
}
```

### 4.3 Modelo de datos (tablas del core)

| Tabla | Campos clave |
|---|---|
| `apps` | `slug` (unique), `name`, `version`, `status` (`active` / `inactive` / `error`), `last_error` (text, nullable), `config` (json), `activated_at`, timestamps |
| spatie | `roles`, `permissions`, `model_has_roles`, `model_has_permissions` + rol `super-admin` sembrado |
| `content_types` | `slug` (unique, ej. `blog.post`), `app_slug`, `label`, `config` (json) |
| Tablas de cada app | creadas por sus propias migraciones (ej. `posts`, `pages`) |

### 4.4 Ciclo de activación

1. **Boot:** cada ServiceProvider de app se auto-descubre vía Composer y su constructor/base llama a `PluginManager::isActive($slug)`, que lee `bootstrap/cache/active-apps.php` (cero queries a BD por petición). Si está inactiva → `boot()` retorna sin registrar nada.
2. **Activar** (solo super-admin):
   - Validar manifest (schema, versión de PHP/Laravel, dependencias `apps` activas).
   - Ejecutar migraciones de la app (`migrate --path=apps/{slug}/database/migrations`) y seeder declarado.
   - Si todo OK: `status=active`, registrar permisos spatie, reescribir `active-apps.php`.
   - Si falla: `status=error` + `last_error`; **no** entra al cache; la UI ofrece "Reintentar" (las migraciones de Laravel son idempotentes, el reintento retoma donde falló).
3. **Desactivar:** `status=inactive`, reescribe cache, **elimina** los permisos spatie de la app (los re-crea al reactivar), oculta rutas/menú/widgets. **No borra datos.**
4. **Desinstalar:** acción aparte con confirmación (el usuario escribe el slug) → rollback de las migraciones de la app por path → limpia `config`, `last_error`, `activated_at` y sus filas en `content_types` → `status=inactive`.

### 4.5 Enrutado

- Rutas de apps montadas bajo `/apps/{slug}/...` con middleware `web`, `auth` y `app.access:{slug}`.
- `app.access` verifica: app `active` + usuario tiene el permiso requerido.
- Ruta de app inactiva → **404**. App activa sin permiso → **403**.

## 5. Contrato del plugin (`packages/contracts`)

```php
class BlogServiceProvider extends PluginServiceProvider
{
    protected string $slug = 'blog';   // debe coincidir con app.json

    protected function bootPlugin(): void
    {
        $this->routes('routes/web.php');                  // montado bajo /apps/blog
        $this->menu('Blog', icon: 'book', route: 'blog.index',
                    permission: 'blog.access', order: 20);
        $this->permissions(['blog.access', 'blog.publish']);
        $this->widget(PostsCountWidget::class, permission: 'blog.access');
        $this->contentType('blog.post', label: 'Post');
    }
}
```

Piezas del contrato:

- **`PluginServiceProvider`** (clase abstracta en contracts): hace el gating de activación, atrapa `Throwable` de `bootPlugin()` (log + app oculta en esa petición, nunca tumba el sitio) y expone los helpers `routes()`, `menu()`, `permissions()`, `widget()`, `contentType()`.
- **Interfaces de registro** en contracts, implementadas por core y enlazadas en el contenedor: `MenuRegistry`, `WidgetRegistry`, `ContentTypeRegistry`, `PluginState` (lectura de estado activo), `PluginRepository` (CRUD de la tabla `apps`).
- **`PluginManager`** (en core): orquesta activar / desactivar / desinstalar / validar manifest / reconstruir cache.
- **Permisos:** declarados en manifest, sembrados al activar, retirados al desactivar. Menús y vistas se filtran por permisos del usuario.
- **Widgets:** clases Livewire registradas por apps; el dashboard las renderiza filtrando por app activa + permiso, con try/catch aislado por widget (log + ocultar).
- **Tipos de contenido:** al activar, la app registra sus tipos en `content_types`; otras apps (reportes, búsqueda) pueden consultarlos para consumir contenido ajeno.
- **Dependencias entre apps:** `requires.apps` en el manifest; no se activa la app si su dependencia no está activa.

## 6. Panel admin

| Pantalla | Contenido |
|---|---|
| `/admin/apps` | Lista de apps descubiertas en disco + estado, versión, última acción; botones **Activar / Desactivar / Desinstalar / Configurar**; errores visibles con "Reintentar". |
| `/admin/apps/{slug}/config` | Formulario generado desde `config` del manifest; guarda en la columna `config`. |
| `/admin/roles` | CRUD de roles + asignación de permisos (spatie). |
| `/admin/users` | Gestión de usuarios (Breeze solo trae auth; el panel la añade). |
| Dashboard (`/`) | Widgets de apps activas con permiso del usuario; fallo de widget aislado. |

- Sidebar: secciones core (Dashboard, Usuarios, Apps, Roles) + entradas de menú de apps activas, filtradas por permiso.
- Acciones Livewire con feedback (toasts) y actualización optimista del estado.
- CLI: `php artisan apps:list`, `apps:activate {slug}`, `apps:deactivate {slug}`, `apps:verify` (revalida manifests y reconstruye `active-apps.php` tras deploys).

**Seguridad:** todo `/admin/*` bajo middleware `role:super-admin`; políticas (Policies) en `AppController`; desinstalar requiere escribir el slug; solo super-admin activa apps.

## 7. Manejo de errores

| Fallo | Comportamiento |
|---|---|
| Migración falla al activar | `status=error` + `last_error`; fuera del cache; UI con "Reintentar" (reintento idempotente). |
| App activa revienta al boot | `PluginServiceProvider` atrapa el error, loguea con slug, oculta la app en esa petición. `apps:verify` marca `error` en BD. |
| Widget falla | try/catch aislado → log + widget oculto; el dashboard sigue funcionando. |
| Cache desactualizada | `active-apps.php` guarda versión + hash del manifest; se reescribe en cada cambio de estado; `apps:verify` lo reconstruye. |
| Ruta de app inactiva | 404. |

## 8. Plan de pruebas (TDD)

- **Unit (Pest):** `PluginManager` — activar, desactivar, desinstalar, validación de manifest, dependencias entre apps, escritura/lectura del cache, gating del `PluginServiceProvider`.
- **Feature (Pest + RefreshDatabase):**
  - Panel admin: rutas protegidas (sin sesión → login; sin rol super-admin → 403).
  - Activar crea las tablas de la app; desactivar oculta rutas (404); desinstalar revierte migraciones.
  - Gating: app activa + permiso → 200; activa sin permiso → 403; inactiva → 404.
  - Widgets filtrados por app activa y permiso; widget que lanza excepción no rompe el dashboard.
  - Permisos spatie sembrados al activar y retirados al desactivar.
- **Integración del contrato:** la app `apps/notes` (CRUD mínimo) construida contra `contracts` funciona de punta a punta; si el contrato no sirve para una app real, el test falla.

## 9. Fases de implementación

1. **Fase 1 — Kernel:** scaffold Laravel 12 + Breeze (Livewire/Flux), `contracts` + `core`, tabla `apps`, `PluginManager`, activación con migraciones, panel `/admin/apps`, gating de rutas, spatie + seed de roles.
2. **Fase 2 — APIs de app:** menú, permisos, widgets de dashboard, tipos de contenido, CLI `apps:*`.
3. **Fase 3 — Validación:** app `notes` (ejemplo) + app `reportes` que consuma `content_types` de otras apps (prueba real de interoperabilidad).

El plan de implementación inicial cubre la **Fase 1**; las fases 2 y 3 reciben su propio plan cuando la anterior esté entregada.

## 10. Fuera de alcance (YAGNI)

- Multisitio / multi-tenant / dominios por sitio.
- Marketplace de terceros, instalación en runtime desde URL, sandboxing de código no confiable.
- Auto-update de apps desde repositorios remotos (Packagist, zip subido).
- Componentes Flux Pro, billing, API pública.

El manifest `app.json` + la capa `contracts` dejan la puerta abierta a instalación dinámica futura sin rehacer el núcleo.

## 11. Criterios de aceptación

1. `composer create-project` + Breeze (Livewire) funcionando con login/logout/registro.
2. Una app en `apps/` se descubre automáticamente y aparece en `/admin/apps` como "inactiva".
3. El super-admin la activa → migraciones corren, permisos creados, rutas bajo `/apps/{slug}` responden 200, entrada visible en el sidebar.
4. Desactivar → rutas 404, entrada del menú desaparece, datos intactos.
5. Un usuario sin permiso no ve ni puede acceder a la app (403/404).
6. Una app con migración rota queda en `error`, no tumba el sitio y permite reintentar.
7. La suite Pest completa pasa.
