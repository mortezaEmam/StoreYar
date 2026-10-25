<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Catalog\Application\Commands\AddProductBarcode\AddProductBarcodeCommand;
use StoreYar\Modules\Catalog\Application\Queries\LookupByBarcode\LookupByBarcodeQuery;
use StoreYar\Modules\Catalog\Infrastructure\Persistence\Eloquent\ProductBarcodeModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Bus\Query\QueryBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class BarcodeController
{
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
        private BusinessContext $business,
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        $barcode = $request->query('barcode', $request->input('barcode'));
        if (! is_string($barcode) || trim($barcode) === '') {
            return response()->json(['message' => 'barcode is required'], 422);
        }

        $result = $this->queries->ask(
            new LookupByBarcodeQuery(
                organizationId: $this->business->businessId(),
                barcode: $barcode,
            ),
        );

        if ($result === null) {
            return response()->json(['message' => 'Barcode not found'], 404);
        }

        return response()->json(['data' => $result]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string', 'size:26'],
            'barcode' => ['required', 'string', 'max:64'],
            'type' => ['sometimes', 'string', 'max:32'],
            'is_primary' => ['sometimes', 'boolean'],
            'unit_id' => ['sometimes', 'nullable', 'string', 'size:26'],
        ]);

        $row = $this->commands->dispatch(
            new AddProductBarcodeCommand(
                organizationId: $this->business->businessId(),
                productId: $data['product_id'],
                barcode: $data['barcode'],
                type: $data['type'] ?? 'INTERNAL',
                isPrimary: (bool) ($data['is_primary'] ?? false),
                unitId: $data['unit_id'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'barcode' => $row->barcode,
                'type' => $row->type,
                'is_primary' => (bool) $row->is_primary,
            ],
        ], 201);
    }

    public function byProduct(string $productId): JsonResponse
    {
        $items = ProductBarcodeModel::query()
            ->where('organization_id', $this->business->businessId())
            ->where('product_id', $productId)
            ->orderByDesc('is_primary')
            ->get(['id', 'barcode', 'type', 'is_primary', 'unit_id']);

        return response()->json(['data' => $items]);
    }
}
