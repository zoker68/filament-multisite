# FilamentMultisite
Simple plugin for multisite

# Install

```bash
composer require zoker/filament-multisite
```

## Publish config

```bash
php artisan vendor:publish --tag=filament-multisite-config
```

## Publish migrations

```bash
php artisan vendor:publish --tag=filament-multisite-migrations
```

## Add to Filament Service Provider
```php
->plugin(\Zoker\FilamentMultisite\Multisite::make())
```

# Using

## Route definition 
Define routes for multisite in `routes/web.php`
```php
Route::multisite(function () {
   /** All Routes for multisite */ 
});
```

## Translatable routes
Key must be in `resources/lang` directory for each locale. Not in package directory. Only in root directory.
```php
Route::translatable(function () {
   Route::get(__('key'), function () {
      return view('welcome');
   }); 
});
```

## Link generation
Use `multisite_route()` instead of `route()`
```php
multisite_route('route', ['slug' => 'slug'])
```
