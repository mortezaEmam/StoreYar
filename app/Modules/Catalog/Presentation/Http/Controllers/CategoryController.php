<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\AssignProductCategory\AssignProductCategoryCommand;
use StoreYar\Modules\Catalog\Application\Commands\CreateCategory\CreateCategoryCommand;
use StoreYar\Modules\Catalog\Application\Queries\ListCategories\ListCategoriesQuery;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class CategoryController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = $this->queries->ask(
            new ListCategoriesQuery(organizationId: $this->business->businessId()),
        );

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $name = (string) $request->input('name', '');
        $request->validate(['name' => ['required', 'string', 'max:120']]);

        try {
            $row = $this->commands->dispatch(
                new CreateCategoryCommand(
                    organizationId: $this->business->businessId(),
                    name: $name,
                ),
            );

            return response()->json(['data' => $row], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function assignToProduct(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'category_id' => ['nullable', 'string', 'size:26'],
        ]);

        try {
            $this->commands->dispatch(
                new AssignProductCategoryCommand(
                    organizationId: $this->business->businessId(),
                    productId: $id,
                    categoryId: $request->input('category_id'),
                ),
            );

            return response()->json(['message' => 'Category assigned.']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
