<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Exceptions\ProductSkuAlreadyExists;
use StoreYar\Modules\Catalog\Presentation\Http\Requests\CreateProductRequest;
use StoreYar\Modules\Catalog\Presentation\Http\Resources\ProductResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class ProductController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            /** @var Product $product */
            $product = $this->commands->dispatch(
                new CreateProductCommand(
                    organizationId: $this->business->businessId(),
                    name: $request->string('name')->toString(),
                    sku: $request->string('sku')->toString(),
                ),
            );

            return (new ProductResource($product))
                ->response()
                ->setStatusCode(201);
        } catch (ProductSkuAlreadyExists $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
