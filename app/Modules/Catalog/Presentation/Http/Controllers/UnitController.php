<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\CreateUnit\CreateUnitCommand;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\UnitModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class UnitController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(): JsonResponse
    {
        $items = UnitModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'symbol', 'is_base']);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['sometimes', 'nullable', 'string', 'max:16'],
            'is_base' => ['sometimes', 'boolean'],
        ]);

        $unit = $this->commands->dispatch(
            new CreateUnitCommand(
                organizationId: $this->business->businessId(),
                code: $data['code'],
                name: $data['name'],
                symbol: $data['symbol'] ?? null,
                isBase: (bool) ($data['is_base'] ?? false),
            ),
        );

        return response()->json([
            'data' => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'is_base' => (bool) $unit->is_base,
            ],
        ], 201);
    }
}
