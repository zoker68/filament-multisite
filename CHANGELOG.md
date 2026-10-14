# Changelog

## 2.5.1 — 2026-10-14

### Fixed

- `Site::getUsingLocales()` returns a list: when several sites shared a locale, the array kept gaps in its
  keys (`[0 => 'en', 2 => 'de']`) and `json_encode` turned it into an object. A value cached by an older
  version is re-indexed on read, so no `cache:clear` is needed after the update.

### Added

- Automatic `hreflang` alternates: when nothing called `AlternateLinks::set()` during the request,
  `AlternateLinks::get()` / `getCanonicalUrl()` (and so `<x-multisite::alternateLinksHead />` and
  `<x-multisite::sitePicker />`) derive the links from the current route — every active site of the
  current group gets the same route and parameters via `multisite_route()`, without the query string.
  Only routes registered by `Route::multisite()` / `Route::translated()` qualify; a site whose variant
  of the route is not registered is skipped instead of being linked to another site's URL.
- The macros mark their routes with route metadata (`FilamentMultisiteRouteServiceProvider::ROUTE_METADATA_KEY`);
  check with `FilamentMultisiteRouteServiceProvider::isMultisiteRoute($route)`.
- `multisite_route_name($name, $site, $locale = null)` — the route name `multisite_route()` resolves for a
  site, or `null` when none is registered. `multisite_route()` behaves as before.

### Changed

- Requires `laravel/framework` **^13.17** (route metadata).
- **Pages that used to output only the canonical now output `hreflang` alternates** — every page inside
  `Route::multisite()` / `Route::translated()` whose controller does not call `AlternateLinks::set()`
  (utility pages such as cart, search, account included). Opt a page out with `AlternateLinks::set([])`.
- `MultisiteMiddleware` resets `AlternateLinks` at the start of each request (no state leaking between
  requests on Octane).
- Error pages rendered through the exception handler get no alternates.

### Fixed

- PHPStan: `AlternateLinks::get()` item shape includes `isDefault`; stale `@phpstan-ignore-line` comments removed.
