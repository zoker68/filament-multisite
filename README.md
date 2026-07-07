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
- Laravel 12+
- Filament 4+ (for admin panel integration)

---

## Installation

1. **Install the package**

   ```bash
   composer require zoker/filament-multisite
   ```

2. **Publish and run migrations**

   ```bash
   php artisan vendor:publish --tag=filament-multisite-migrations
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

Use the `translatable` macro for localized paths. Translation keys should be placed in your application's `resources/lang` directory:

```php
Route::translatable(function () {
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

The package provides automatic generation of alternate links and canonical URLs for SEO optimization.

### Frontend Components

#### Alternate Links Head

Include alternate links in your HTML head section:

```blade
<x-multisite::alternateLinksHead />
```

This will generate:
- `<link rel="canonical">` for the specified canonical site (if configured)
- `<link rel="alternate" hreflang="...">` for all available sites

#### Site Picker

Display a site/language switcher component:

```blade
<x-multisite::sitePicker />
```

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=multisite-config
```

Canonical URLs are **self-canonical**: each page declares its own current-site URL
as canonical (or a URL set explicitly via `AlternateLinks::setCanonicalUrl()`).
Cross-language canonicals are intentionally never generated — they would drop the
translated versions from the index; the language relationship is expressed via
`hreflang` alternates (incl. `x-default` → the default site) instead.

### Programmatic Usage

#### Setting Alternate Links

```php
use Zoker\FilamentMultisite\Services\AlternateLinks;

// Set alternate links in your controller
public function show(Category $category)
{
    $links = [];
    foreach ($sites as $site) {
        $links[] = [
            'site' => $site,
            'url' => localized_url_for_site($site),
        ];
    }
    AlternateLinks::set($links);
    
    return view('category.show', compact('category'));
}
```

#### Custom Canonical URL

```php
// Set a custom canonical URL (overrides config)
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
