<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StoreYar\Modules\Catalog\Application\Commands\ActivateProduct\ActivateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\ArchiveProduct\ArchiveProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateProduct\CreateProductCommand;
use StoreYar\Modules\Catalog\Application\Commands\RenameProduct\RenameProductCommand;
use StoreYar\Modules\Catalog\Application\Queries\GetProductById\GetProductByIdQuery;
use StoreYar\Modules\Catalog\Application\Queries\ListProductsByOrganization\ListProductsByOrganizationQuery;
use StoreYar\Modules\Catalog\Domain\Aggregates\Product;
use StoreYar\Modules\Catalog\Domain\Exceptions\ProductSkuAlreadyExists;
use StoreYar\Modules\Catalog\Presentation\Http\Requests\CreateProductRequest;
use StoreYar\Modules\Catalog\Presentation\Http\Requests\RenameProductRequest;
use StoreYar\Modules\Catalog\Presentation\Http\Resources\ProductResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class ProductController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}


    public function index(): JsonResponse
    {
        $products = $this->queries->ask(
            new ListProductsByOrganizationQuery(
                organizationId: $this->business->businessId(),
            ),
        );

        return ProductResource::collection($products)
            ->response()
            ->setStatusCode(200);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->queries->ask(
            new GetProductByIdQuery(productId: $id),
        );

        if ($product === null) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        // جلوگیری از نشت داده بین tenantها
        if ($product->organizationId() !== $this->business->businessId()) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(200);
    }

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
        } catch (ProductSkuAlreadyExists|\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function activate(string $id): JsonResponse
    {
        try {
            /** @var Product $product */
            $product = $this->commands->dispatch(
                new ActivateProductCommand(
                    productId: $id,
                    organizationId: $this->business->businessId(),
                ),
            );

            return (new ProductResource($product))->response();
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Product not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function archive(string $id): JsonResponse
    {
        try {
            /** @var Product $product */
            $product = $this->commands->dispatch(
                new ArchiveProductCommand(
                    productId: $id,
                    organizationId: $this->business->businessId(),
                ),
            );

            return (new ProductResource($product))->response();
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Product not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }


    public function rename(RenameProductRequest $request, string $id): JsonResponse
    {
        try {
            /** @var Product $product */
            $product = $this->commands->dispatch(
                new RenameProductCommand(
                    productId: $id,
                    organizationId: $this->business->businessId(),
                    name: $request->string('name')->toString(),
                ),
            );

            return (new ProductResource($product))->response();
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Product not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
