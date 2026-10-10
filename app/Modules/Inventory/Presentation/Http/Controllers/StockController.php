<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StoreYar\Modules\Inventory\Application\Commands\AdjustStock\AdjustStockCommand;
use StoreYar\Modules\Inventory\Application\Commands\InitializeStock\InitializeStockCommand;
use StoreYar\Modules\Inventory\Application\Queries\GetStockByProduct\GetStockByProductQuery;
use StoreYar\Modules\Inventory\Application\Queries\ListStockByOrganization\ListStockByOrganizationQuery;
use StoreYar\Modules\Inventory\Domain\Aggregates\StockItem;
use StoreYar\Modules\Inventory\Domain\Exceptions\InsufficientStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\ProductNotFoundForStock;
use StoreYar\Modules\Inventory\Domain\Exceptions\StockItemAlreadyExists;
use StoreYar\Modules\Inventory\Presentation\Http\Requests\AdjustStockRequest;
use StoreYar\Modules\Inventory\Presentation\Http\Requests\InitializeStockRequest;
use StoreYar\Modules\Inventory\Presentation\Http\Resources\StockItemResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class StockController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = $this->queries->ask(
            new ListStockByOrganizationQuery(
                organizationId: $this->business->businessId(),
            ),
        );

        return StockItemResource::collection($items)
            ->response()
            ->setStatusCode(200);
    }

    public function show(string $productId): JsonResponse
    {
        $item = $this->queries->ask(
            new GetStockByProductQuery(
                organizationId: $this->business->businessId(),
                productId: $productId,
            ),
        );

        if ($item === null) {
            return response()->json(['message' => 'Stock item not found.'], 404);
        }

        return (new StockItemResource($item))
            ->response()
            ->setStatusCode(200);
    }

    public function store(InitializeStockRequest $request): JsonResponse
    {
        try {
            /** @var StockItem $item */
            $item = $this->commands->dispatch(
                new InitializeStockCommand(
                    organizationId: $this->business->businessId(),
                    productId: $request->string('product_id')->toString(),
                    quantity: $request->integer('quantity', 0),
                ),
            );

            return (new StockItemResource($item))
                ->response()
                ->setStatusCode(201);
        } catch (ProductNotFoundForStock $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (StockItemAlreadyExists $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function adjust(AdjustStockRequest $request, string $productId): JsonResponse
    {
        try {
            /** @var StockItem $item */
            $item = $this->commands->dispatch(
                new AdjustStockCommand(
                    organizationId: $this->business->businessId(),
                    productId: $productId,
                    amount: $request->integer('amount'),
                    direction: $request->string('direction')->toString(),
                ),
            );

            return (new StockItemResource($item))
                ->response()
                ->setStatusCode(200);
        } catch (InsufficientStock $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Stock item not found.' ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
}
