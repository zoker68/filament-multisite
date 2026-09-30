# Filament Multisite

Manage multiple sites within a single Laravel application with first‑class Filament integration.

The package provides:

- Multi‑site routing (domains / prefixes / locales)
- Localized routes and helpers for URL generation
- Per‑site models via `HasMultisite`
- Filament admin integration with site switching

---

## Requirements

- PHP 8.4+
- Laravel 13.17+
- Filament 4+ (for admin panel integration)

---

## Installation

1. **Install the package**

   ```bash
   composer require zoker/filament-multisite
   ```

2. **Publish and run migrations**

   ```bash
   php artisan vendor:publish --tag=multisite-migrations
   php artisan migrate
   ```

3. **Register the Filament plugin**

   In your Filament panel configuration:

   ```php
   use Zoker\FilamentMultisite\Multisite;

   // In your Filament panel definition
   ->plugin(Multisite::make())
   ```

---

## Frontend usage

### Defining multisite routes

Create site‑specific routes in `routes/web.php` using the `multisite` macro:

```php
use Illuminate\Support\Facades\Route;

Route::multisite(function () {
    // These routes will be available for all sites
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Add more site‑specific routes here
});
```

### Translatable routes

Use the `translated` macro for localized paths. Translation keys should be placed in your application's `resources/lang` directory:

```php
Route::translated(function () {
    Route::get(__('routes.about'), [AboutController::class, 'index'])->name('about');
});
```

### Generating URLs

Use the `multisite_route()` helper to generate URLs that respect the current site and locale:

```php
// Basic usage
$url = multisite_route('home');

// With parameters
$url = multisite_route('products.show', ['product' => $product]);

// Absolute URLs
$url = multisite_route('login', [], true);
```

---

## Managing the current site (frontend)

### Facade: `SiteManager`

```php
use Zoker\FilamentMultisite\Facades\SiteManager;

// Get current site
$currentSite = SiteManager::getCurrentSite();

// Set current site by ID
SiteManager::setCurrentSiteById(1);

// Set current site by request
SiteManager::setCurrentSiteByRequest($request);
```

### Helper: `currentSite()`

```php
// Get current site
$site = currentSite();
```

### Middleware

Use middleware to automatically resolve the current site from the request:

```php
use Illuminate\Support\Facades\Route;
use Zoker\FilamentMultisite\Http\Middleware\MultisiteMiddleware;

Route::middleware([MultisiteMiddleware::class])->group(function () {
    // Your routes here
});
```

You can also register the middleware in the `web` group.

---

## Filament integration

### Site switching in Filament

In Filament the active site is managed via the `FilamentSiteManager`. The currently selected site is stored in the session and is used automatically by models with the `HasMultisite` trait when Filament is serving the request.

Add the `SiteSwitcher` action to your Filament resources:

```php
use Zoker\FilamentMultisite\Filament\Actions\SiteSwitcher;

public function getActions(): array
{
    return [
        SiteSwitcher::make(),
    ];
}
```

## Translatable models

To use translatable models, add the `HasTranslations` trait to your model and define the translatable attributes.  
These attributes must be stored in JSON columns in your database.

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class YourModel extends Model
{
    use HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['attribute'];
}

## Per‑site models: `HasMultisite`

To store separate content per site, add the `HasMultisite` trait to your model:

```php
use Illuminate\Database\Eloquent\Model;
use Zoker\FilamentMultisite\Traits\HasMultisite;

class YourModel extends Model
{
    use HasMultisite;
}
```

This trait adds a global scope on the `site_id` column:

- In **frontend/HTTP** context the current site is resolved via `SiteManager`.
- In **Filament** context the current site is resolved via `FilamentSiteManager` (selected site in the admin panel).

The trait also provides helpers like `createForCurrentSite()` to create records for the active site.

### Filament resources: `HasMultisiteResource`

To make a Filament resource aware of the active site, use the `HasMultisiteResource` trait:

```php
use Filament\Resources\Resource;
use Zoker\FilamentMultisite\Traits\HasMultisiteResource;

class YourResource extends Resource
{
    use HasMultisiteResource;
}
```

---

## Translatable resource traits

For translatable Filament resources you can use:

- `Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableEditRecord` – for `Resource/Pages/Edit*`
- `Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableListRecord` – for `Resource/Pages/List*`
- `Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableCreateRecord` – for `Resource/Pages/Create*`

---

## Migrations

To link your models to a specific site, add a foreign key to the `sites` table:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zoker\FilamentMultisite\Models\Site;

Schema::table('your_table', function (Blueprint $table) {
    $table->foreignIdFor(Site::class)->constrained()->cascadeOnDelete();
});
```

---

## Alternate Links & Canonical URLs

The package outputs the canonical URL and the `hreflang` alternates of the current page.

### Frontend Components

#### Alternate Links Head

Include alternate links in your HTML head section:

```blade
<x-multisite::alternateLinksHead />
```

This will generate:
- `<link rel="canonical">` — the page's own current-site URL (see below)
- `<link rel="alternate" hreflang="...">` for every alternate site, plus `hreflang="x-default"` → the default site of the group

#### Site Picker

Display a site/language switcher component:

```blade
<x-multisite::sitePicker />
```

### Automatic alternates

When nothing called `AlternateLinks::set()` during the request, the alternates are derived from the
current route: every **active** site of the current site's **group** gets the same route with the same
parameters, built by `multisite_route()`:

- a prefixed site gets its `multisite.*` variant (`/en/page/about`), a site on its own domain an absolute
  URL on that host, and a `Route::translated()` route the path of the target site's locale;
- route parameters are carried over (models via `getRouteKey()`), the query string is dropped;
- a site whose variant of the route is not registered (e.g. a prefixed site added after the routes were
  registered or cached) is skipped rather than linked to another site's URL. With `Route::translated()`
  this includes a locale whose path translates to the same string as another locale's (or has no
  translation and falls back to it): Laravel keeps only the last route registered for a URI.

Only routes registered inside `Route::multisite()` / `Route::translated()` get automatic alternates — the
macros mark their routes with route metadata (`$route->getMetadata('multisite_route')`, or
`FilamentMultisiteRouteServiceProvider::isMultisiteRoute($route)`). Plain `web` routes (`/admin`,
`/livewire/update`, your own routes outside the macros), unnamed and fallback routes, unmatched URLs and
error pages (404 / 500 rendered through the exception handler) get no alternates.

The state is reset by `MultisiteMiddleware` at the start of every request, so it does not leak between
requests on Octane.

### Canonical URL

Canonical URLs are **self-canonical**: each page declares its own current-site URL
(without the query string) as canonical, or a URL set explicitly via `AlternateLinks::setCanonicalUrl()`.
Cross-language canonicals are intentionally never generated — they would drop the
translated versions from the index; the language relationship is expressed via
`hreflang` alternates (incl. `x-default` → the default site) instead.

### Programmatic Usage

#### Setting Alternate Links

An explicit `set()` always wins over the automatic alternates. It is only needed when the sites' URLs
cannot be derived from the route — e.g. translated slugs, so each site has a different path — or when
only some sites have the page:

```php
use Zoker\FilamentMultisite\Services\AlternateLinks;

public function show(Category $category)
{
    $links = [];
    foreach ($category->translations as $translation) {
        $links[] = [
            'site' => $translation->site,
            'url' => multisite_route('category.show', ['category' => $translation->slug], site: $translation->site),
        ];
    }
    AlternateLinks::set($links);

    return view('category.show', compact('category'));
}
```

#### Disabling alternates on a page

```php
AlternateLinks::set([]); // no hreflang on this page, the self-canonical stays
```

#### Custom Canonical URL

```php
// Set a custom canonical URL
AlternateLinks::setCanonicalUrl('https://example.com/custom-url');

// Get the current canonical URL
$canonicalUrl = AlternateLinks::getCanonicalUrl();
```

---

## Events

The package dispatches events that you can listen for:

- `Zoker\FilamentMultisite\Events\SiteChanged` – dispatched when the active site changes.

---

## Testing

Run the tests with:

```bash
composer test
```
