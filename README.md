# Filament Multisite

A powerful package for managing multiple sites within a single Laravel Filament application. This package provides tools for handling site-specific routes, translations, and configurations.

## Features

- Multi-site route management
- Built-in localization support
- Multisite link generation
- Site management
- Filament integration

## Installation

1. Install the package via Composer:

```bash
composer require zoker/filament-multisite
```

3. Publish and run migrations:

```bash
php artisan vendor:publish --tag=filament-multisite-migrations
php artisan migrate
```

4. Add to your Filament panel configuration:

```php
use Zoker\FilamentMultisite\Multisite;

// In your Filament panel configuration
->plugin(Multisite::make())
```

## Usage

### Defining Routes

Create site-specific routes in `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::multisite(function () {
    // These routes will be available for all sites
    Route::get('/', [HomeController::class, 'index']);
    
    // Add more site-specific routes here
});
```

### Translatable Routes

For localized routes, use the `translatable` method. Translation keys should be placed in your application's `resources/lang` directory.

```php
Route::translatable(function () {
    Route::get(__('routes.about'), [AboutController::class, 'index'])->name('about');
});
```

### Generating URLs

Use the `multisite_route()` helper to generate URLs that respect the current site context:

```php
// Basic usage
$url = multisite_route('home');

// With parameters
$url = multisite_route('products.show', ['product' => $product]);

// Absolute URLs
$url = multisite_route('login', [], true);
```

### Managing the Current Site

#### Using the Facade

```php
use Zoker\FilamentMultisite\Facades\SiteManager;

// Get current site
$currentSite = SiteManager::getCurrentSite();

// Set a current site by ID
SiteManager::setCurrentSiteById(1);

// Set a current site by request
SiteManager::setCurrentSiteByRequest($request);
```

#### Using the Helper

```php
// Get current site
$site = currentSite();
```

### Middleware

The package includes middleware to automatically set the current site based on the request:

```php
// In your route group
Route::middleware([\Zoker\FilamentMultisite\MultisiteMiddleware::class])->group(function () {
    // Your routes here
});
```

or add the middleware to the `web` middleware group.

### Translatable Models

Add the `ModelHasTranslations` trait to your model and define the translatable attributes:

```php
use Zoker\FilamentMultisite\Traits\Translatable\ModelHasTranslations;

class YourModel extends Model
{
    use ModelHasTranslations;

    public array $translatable = ['attribute'];
}
```

## SiteSwitcher

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

## Translate Resource traits:

- `Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableEditRecord`
- `Zoker\FilamentMultisite\Traits\Translatable\Resources\Pages\TranslatableListRecord`
- 

## Events

The package dispatches events that you can listen for:

- `Zoker\FilamentMultisite\Events\SiteChanged`: Dispatched when the active site changes

## Testing

Run the tests with:

```bash
composer test
```

