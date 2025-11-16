# HasMultisite Trait

The `HasMultisite` trait provides multisite functionality for Laravel models. It automatically adds a global scope to filter records by the current site.

## Installation

Add the trait to your model:

```php
use Zoker\FilamentMultisite\Traits\HasMultisite;

class Product extends Model
{
    use HasMultisite;
}
```

## Core Features

### Global Scope

The trait automatically adds a global scope that filters all queries by `site_id` of the current site:

```php
// Automatically filtered by current site
$products = Product::all();
Product::where('active', true)->get();
```

### Scopes

#### `forSite($site)` - Get records for specific site
Ignores global scope and returns records only for the specified site.

```php
// By site ID
$products = Product::forSite(1)->get();

// By Site object
$site = Site::find(1);
$products = Product::forSite($site)->get();
```

#### `allSites()` - Get records from all sites
Removes global scope and returns records from all sites.

```php
$allProducts = Product::allSites()->get();
```

#### `forSites($siteIds)` - Records for multiple sites
```php
$products = Product::forSites([1, 2, 3])->get();
```

#### `exceptSite($site)` - Exclude records from specific site
```php
$products = Product::exceptSite(1)->get();
```

### Model Methods

#### `site()` - Relationship with Site model
```php
$product = Product::find(1);
$site = $product->site; // Get Site object
```

#### `setSite($site)` - Set site for model
```php
$product = new Product();
$product->setSite(1); // By ID
$product->setSite($site); // By Site object
$product->save(); // Save model
```

### Factory Methods

#### `createForCurrentSite($attributes)` - Create record for current site
```php
$product = Product::createForCurrentSite([
    'name' => 'New Product',
    'price' => 99.99
]);
```

#### `createForSite($site, $attributes)` - Create record for specific site
```php
$product = Product::createForSite(1, [
    'name' => 'New Product',
    'price' => 99.99
]);
```

## Usage Examples

### Basic Usage

```php
use Zoker\FilamentMultisite\Traits\HasMultisite;

class Article extends Model
{
    use HasMultisite;
}

// Get all articles from current site
$articles = Article::all();

// Get articles from specific site
$siteArticles = Article::forSite(2)->get();

// Get articles from all sites
$allArticles = Article::allSites()->get();
```

### Advanced Usage
```php
// Create article for current site
$article = Article::createForCurrentSite([
    'title' => 'New Article',
    'content' => 'Article content'
]);

// Create article for another site
$article = Article::createForSite($otherSite, [
    'title' => 'Article for other site',
    'content' => 'Content'
]);

// Get articles from multiple sites
$articles = Article::forSites([1, 3, 5])->get();

// Exclude articles from specific site
$articles = Article::exceptSite(2)->get();
```

### Controller Usage
```php
class ProductController extends Controller
{
    public function index()
    {
        // Automatically filtered by current site
        $products = Product::paginate(10);
        
        return view('products.index', compact('products'));
    }
    
    public function showAll()
    {
        // Get products from all sites
        $allProducts = Product::allSites()->paginate(10);
        
        return view('admin.products', compact('allProducts'));
    }
    
    public function siteProducts($siteId)
    {
        // Get products from specific site
        $products = Product::forSite($siteId)->paginate(10);
        
        return view('products.site', compact('products'));
    }
}
```

## Model Requirements

A model using the `HasMultisite` trait must have:
- `site_id` field in the database table
- Migration with foreign key to `sites` table

Example migration:
```php
use Zoker\FilamentMultisite\Models\Site;

Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignIdFor(Site::class)->nullable()->constrained()->onDelete('set null');
});
```
