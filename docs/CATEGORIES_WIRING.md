# Wiring categories

## 1. CatalogServiceProvider::register()

```php
use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use StoreYar\Modules\Catalog\Application\Commands\CreateCategory\CreateCategoryCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateCategory\CreateCategoryHandler;
use StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory\AssignProductCategoryCommand;
use StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory\AssignProductCategoryHandler;
use StoreYar\Modules\Catalog\Application\Queries\ListCategories\ListCategoriesQuery;
use StoreYar\Modules\Catalog\Application\Queries\ListCategories\ListCategoriesHandler;

$this->app->singleton(CategoryRepository::class, EloquentCategoryRepository::class);

$registry->register(CreateCategoryCommand::class, CreateCategoryHandler::class);
$registry->register(AssignProductCategoryCommand::class, AssignProductCategoryHandler::class);
$queryRegistry->register(ListCategoriesQuery::class, ListCategoriesHandler::class);
```

## 2. routes/api.php

```php
Route::middleware(['auth.session', 'business.context', 'org.member'])
    ->prefix('categories')
    ->group(base_path('app/Modules/Catalog/Presentation/routes_categories.php'));
```

## 3. app/Modules/Catalog/Presentation/routes.php — add:

```php
use StoreYar\Modules\Catalog\Presentation\Http\Controllers\CategoryController;

Route::patch('/{id}/category', [CategoryController::class, 'assignToProduct']);
```

## 4. ProductModel fillable — add `category_id` if you list fillable.

## 5. ProductResource — optional:

```php
'category_id' => /* from model if available */,
```

For list products, if resource uses domain aggregate without category, add a
DB column read in repository later. UI can still assign and list categories
separately.
