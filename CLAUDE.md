# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

`zoker/filament-multisite` (`Zoker\FilamentMultisite\`) — a Filament 5 plugin for managing multiple domains/locales in one Laravel app. Models opting into the `HasMultisite` trait are transparently scoped per site. Developed inside a host workbench app where it is symlinked into `vendor/` as a Composer `path` repo and shares the **root `vendor/`**. Standalone, independently publishable. `zoker/shop` and `zoker/filament-static-pages` depend on it.

## Commands

From the host root, via Sail:

```bash
# From the workbench root (PHPUnit-style tests, shared root vendor):
./vendor/bin/sail composer test:multisite
# (wraps: phpunit -c packages/zoker/FilamentMultisite/phpunit.xml --bootstrap vendor/autoload.php)
./vendor/bin/sail php vendor/bin/phpstan analyse -c packages/zoker/FilamentMultisite/phpstan.neon
./vendor/bin/sail pint packages/zoker/FilamentMultisite
```

Tests use Orchestra Testbench + Pest 4; `tests/TestCase.php` registers the providers and runs against in-memory SQLite.

## Architecture

Two providers (`src/Providers/`):
- **`FilamentMultisiteServiceProvider`** (Spatie `PackageServiceProvider`) — migrations (`sites` table + columns), views, translations, config, helpers; sets the Spatie Translatable fallback locale.
- **`FilamentMultisiteRouteServiceProvider`** (plain `ServiceProvider`) — registers `Route::multisite()` (site-prefixed URLs) and `Route::translated()` (localized routes) macros; caches available prefixes/locales (3h TTL).

`src/` highlights: `Models/Site` (`getForDomain()`, soft deletes, locales) · `Services/` `SiteManager` + `FilamentSiteManager` · `Traits/` `HasMultisite` (global `site_id` scope), `HasMultisiteResource`, `Translatable/*` (Spatie Translatable wiring for Filament Create/Edit/List) · `Filament/Resources/Sites/` (CRUD) · `Http/Middleware/` `MultisiteMiddleware`, `SetFilamentLocale` · `View/Components/` `SitePicker`, `AlternateLinksHead` · `Facades/`, `DTO/`, `Events/SiteChanged`, `Observers/SiteObserver`. The `Multisite.php` class is the Filament plugin entry.

## Key convention

`HasMultisite` adds a global scope filtering by the current site's `site_id` — most queries are implicitly per-site. The current site is resolved by domain via `SiteManager`/middleware. Be deliberate when you need cross-site queries (bypass the scope explicitly).

Changes here are real package changes — they must land in this package's upstream repo.