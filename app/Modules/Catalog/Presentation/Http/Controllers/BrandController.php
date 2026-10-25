<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\CreateBrand\CreateBrandCommand;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\BrandModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class BrandController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = BrandModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $brand = $this->commands->dispatch(
            new CreateBrandCommand(
                organizationId: $this->business->businessId(),
                name: $data['name'],
                code: $data['code'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'id' => $brand->id,
                'code' => $brand->code,
                'name' => $brand->name,
                'is_active' => (bool) $brand->is_active,
            ],
        ], 201);
    }
}
