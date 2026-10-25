<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StoreYar\Modules\Inventory\Application\Commands\CreateWarehouse\CreateWarehouseCommand;
use StoreYar\Modules\Inventory\Application\Queries\ListWarehouses\ListWarehousesQuery;
use StoreYar\Modules\Inventory\Presentation\Http\Requests\CreateWarehouseRequest;
use StoreYar\Modules\Inventory\Presentation\Http\Resources\WarehouseResource;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class WarehouseController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = $this->queries->ask(
            new ListWarehousesQuery(organizationId: $this->business->businessId()),
        );

        return response()->json([
            'data' => WarehouseResource::collection(collect($items))->resolve(),
        ]);
    }

    public function store(CreateWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->commands->dispatch(
            new CreateWarehouseCommand(
                organizationId: $this->business->businessId(),
                code: $request->string('code')->toString(),
                name: $request->string('name')->toString(),
                type: $request->input('type', 'MAIN'),
                branchId: $request->input('branch_id'),
                address: $request->input('address'),
            ),
        );

        return response()->json([
            'data' => (new WarehouseResource($warehouse))->resolve(),
        ], 201);
    }
}
