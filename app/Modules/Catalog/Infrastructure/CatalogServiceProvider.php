<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Infrastructure;

use Illuminate\Support\ServiceProvider;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductHandler;
use StoreYar\Modules\Catalog\Domain\Contracts\ProductRepository;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use StoreYar\Shared\Application\Bus\Command\CommandHandlerRegistry;

final class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ProductRepository::class,
            EloquentProductRepository::class,
        );

        $this->app->make(CommandHandlerRegistry::class)->register(
            CreateProductCommand::class,
            CreateProductHandler::class,
        );
    }
}
