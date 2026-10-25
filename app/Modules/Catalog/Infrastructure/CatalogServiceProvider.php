<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Catalog\Application\Commands\ActivateProduct\ActivateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\ActivateProduct\ActivateProductHandler;
use StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct\ArchiveProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct\ArchiveProductHandler;
use StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory\AssignProductCategoryCommand;
use StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory\AssignProductCategoryHandler;
use StoreYar\Modules\Catalog\Application\Commands\ChangeProductPrice\ChangeProductPriceCommand;
use StoreYar\Modules\Catalog\Application\Commands\ChangeProductPrice\ChangeProductPriceHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateCategory\CreateCategoryCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateCategory\CreateCategoryHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductHandler;
use StoreYar\Modules\Catalog\Application\Commands\RenameProduct\RenameProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\RenameProduct\RenameProductHandler;
use StoreYar\Modules\Catalog\Application\Queries\GetProductById\GetProductByIdHandler;
use StoreYar\Modules\Catalog\Application\Queries\GetProductById\GetProductByIdQuery;
use StoreYar\Modules\Catalog\Application\Queries\ListCategories\ListCategoriesHandler;
use StoreYar\Modules\Catalog\Application\Queries\ListCategories\ListCategoriesQuery;
use StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization\ListProductsByOrganizationHandler;
use StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization\ListProductsByOrganizationQuery;
use StoreYar\Modules\Catalog\Domain\Contracts\CategoryRepository;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;
use StoreYar\Shared\Application\Bus\Query\QueryHandlerRegistry;
use StoreYar\Modules\Catalog\Application\Commands\CreateCustomer\CreateCustomerCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateCustomer\CreateCustomerHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateUnit\CreateUnitCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateUnit\CreateUnitHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateBrand\CreateBrandCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateBrand\CreateBrandHandler;
use StoreYar\Modules\Catalog\Application\Commands\AddProductBarcode\AddProductBarcodeCommand;
use StoreYar\Modules\Catalog\Application\Commands\AddProductBarcode\AddProductBarcodeHandler;
use StoreYar\Modules\Catalog\Application\Commands\UpdateProductMaster\UpdateProductMasterCommand;
use StoreYar\Modules\Catalog\Application\Commands\UpdateProductMaster\UpdateProductMasterHandler;
use StoreYar\Modules\Catalog\Application\Commands\CreateSupplier\CreateSupplierCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateSupplier\CreateSupplierHandler;
use StoreYar\Modules\Catalog\Application\Queries\LookupByBarcode\LookupByBarcodeQuery;
use StoreYar\Modules\Catalog\Application\Queries\LookupByBarcode\LookupByBarcodeHandler;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProductRepository::class,
            EloquentProductRepository::class,
        );

        $registry = $this->app->make(CommandHandlerRegistry::class);


        $registry->register(CreateCustomerCommand::class, CreateCustomerHandler::class);
        $registry->register(CreateUnitCommand::class, CreateUnitHandler::class);
        $registry->register(CreateBrandCommand::class, CreateBrandHandler::class);
        $registry->register(AddProductBarcodeCommand::class, AddProductBarcodeHandler::class);
        $registry->register(UpdateProductMasterCommand::class, UpdateProductMasterHandler::class);
        $registry->register(CreateSupplierCommand::class, CreateSupplierHandler::class);
        $registry->register(
            CreateProductCommand::class,
            CreateProductHandler::class,
        );

        $registry->register(
            ActivateProductCommand::class,
            ActivateProductHandler::class,
        );

        $registry->register(
            ArchiveProductCommand::class,
            ArchiveProductHandler::class,
        );

        $registry->register(
            RenameProductCommand::class,
            RenameProductHandler::class,
        );

        $registry->register(
            ChangeProductPriceCommand::class,
            ChangeProductPriceHandler::class,
        );

        $queryRegistry = $this->app->make(QueryHandlerRegistry::class);

        $queryRegistry->register(LookupByBarcodeQuery::class, LookupByBarcodeHandler::class);

        $queryRegistry->register(
            ListProductsByOrganizationQuery::class,
            ListProductsByOrganizationHandler::class,
        );

        $queryRegistry->register(
            GetProductByIdQuery::class,
            GetProductByIdHandler::class,
        );


        $this->app->singleton(CategoryRepository::class, EloquentCategoryRepository::class);

        $registry->register(CreateCategoryCommand::class, CreateCategoryHandler::class);
        $registry->register(AssignProductCategoryCommand::class, AssignProductCategoryHandler::class);
        $queryRegistry->register(ListCategoriesQuery::class, ListCategoriesHandler::class);
    }
}
