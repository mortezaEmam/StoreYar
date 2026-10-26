<?php

declare(strict_types=1);

namespace StoreYar\Modules\Purchasing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StoreYar\Modules\Purchasing\Application\Commands\CreatePurchaseOrder\CreatePurchaseOrderCommand;
use StoreYar\Modules\Purchasing\Application\Commands\ReceiveAgainstPO\ReceiveAgainstPOCommand;
use StoreYar\Modules\Purchasing\Infrastructure\Persistence\Eloquent\PurchaseOrderModel;
use StoreYar\Shared\Application\Bus\Command\CommandBus;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PurchaseOrderController
{
    public function __construct(
        private CommandBus $commands,
        private BusinessContext $business,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = PurchaseOrderModel::query()
            ->where('organization_id', $this->business->businessId())
            ->orderByDesc('created_at')
            ->limit(50);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->query('supplier_id')) {
            $q->where('supplier_id', $request->query('supplier_id'));
        }

        $items = $q->get()->map(fn (PurchaseOrderModel $po) => $this->summary($po));

        return response()->json(['data' => $items]);
    }

    public function show(string $id): JsonResponse
    {
        $po = PurchaseOrderModel::query()
            ->where('id', $id)
            ->where('organization_id', $this->business->businessId())
            ->with('lines')
            ->first();

        if ($po === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $this->detail($po)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'string', 'size:26'],
            'warehouse_id' => ['required', 'string', 'size:26'],
            'branch_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'expected_at' => ['sometimes', 'nullable', 'date'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'submit' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $po = $this->commands->dispatch(
            new CreatePurchaseOrderCommand(
                organizationId: $this->business->businessId(),
                supplierId: $data['supplier_id'],
                warehouseId: $data['warehouse_id'],
                lines: $data['lines'],
                branchId: $data['branch_id'] ?? null,
                notes: $data['notes'] ?? null,
                expectedAt: $data['expected_at'] ?? null,
                currency: $data['currency'] ?? 'IRR',
                submit: (bool) ($data['submit'] ?? true),
            ),
        );

        return response()->json(['data' => $this->detail($po)], 201);
    }

    public function receive(string $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->commands->dispatch(
            new ReceiveAgainstPOCommand(
                organizationId: $this->business->businessId(),
                purchaseOrderId: $id,
                lines: $data['lines'],
                notes: $data['notes'] ?? null,
            ),
        );

        return response()->json([
            'data' => [
                'goods_receipt' => [
                    'id' => $result['goods_receipt']->id,
                    'document_number' => $result['goods_receipt']->document_number,
                    'purchase_order_id' => $result['goods_receipt']->purchase_order_id,
                    'warehouse_id' => $result['goods_receipt']->warehouse_id,
                    'status' => $result['goods_receipt']->status,
                    'received_at' => $result['goods_receipt']->received_at?->toIso8601String(),
                ],
                'purchase_order' => $this->detail($result['purchase_order']),
                'lines' => $result['lines'],
            ],
        ], 201);
    }

    private function summary(PurchaseOrderModel $po): array
    {
        return [
            'id' => $po->id,
            'document_number' => $po->document_number,
            'supplier_id' => $po->supplier_id,
            'warehouse_id' => $po->warehouse_id,
            'status' => $po->status,
            'total_amount' => $po->total_amount,
            'currency' => $po->currency,
            'ordered_at' => $po->ordered_at?->toIso8601String(),
            'created_at' => $po->created_at?->toIso8601String(),
        ];
    }

    private function detail(PurchaseOrderModel $po): array
    {
        $po->loadMissing('lines');

        return [
            'id' => $po->id,
            'document_number' => $po->document_number,
            'supplier_id' => $po->supplier_id,
            'warehouse_id' => $po->warehouse_id,
            'branch_id' => $po->branch_id,
            'status' => $po->status,
            'subtotal_amount' => $po->subtotal_amount,
            'total_amount' => $po->total_amount,
            'currency' => $po->currency,
            'notes' => $po->notes,
            'ordered_at' => $po->ordered_at?->toIso8601String(),
            'expected_at' => $po->expected_at?->toIso8601String(),
            'lines' => $po->lines->map(static fn ($l) => [
                'id' => $l->id,
                'product_id' => $l->product_id,
                'quantity_ordered' => $l->quantity_ordered,
                'quantity_received' => $l->quantity_received,
                'unit_cost_amount' => $l->unit_cost_amount,
                'line_total_amount' => $l->line_total_amount,
            ])->values()->all(),
        ];
    }
}
