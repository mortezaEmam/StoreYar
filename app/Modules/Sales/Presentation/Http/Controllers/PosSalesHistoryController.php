<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleLineModel;
use StoreYar\Modules\Sales\Infrastructure\Persistence\Eloquent\PosSaleModel;
use StoreYar\Shared\Application\Context\BusinessContext;

final class PosSalesHistoryController
{
    public function __construct(
        private BusinessContext $business,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $limit = min(100, max(1, (int) $request->query('limit', 30)));

        $q = PosSaleModel::query()
            ->where('organization_id', $org)
            ->orderByDesc('sold_at')
            ->limit($limit);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->query('session_id')) {
            $q->where('pos_session_id', $request->query('session_id'));
        }

        $rows = $q->get()->map(fn (PosSaleModel $s) => [
            'id' => $s->id,
            'document_number' => $s->document_number,
            'pos_session_id' => $s->pos_session_id,
            'warehouse_id' => $s->warehouse_id,
            'customer_id' => $s->customer_id,
            'subtotal_amount' => $s->subtotal_amount,
            'discount_amount' => $s->discount_amount,
            'total_amount' => $s->total_amount,
            'currency' => $s->currency,
            'payment_method' => $s->payment_method,
            'status' => $s->status,
            'sold_at' => $s->sold_at?->toIso8601String(),
        ]);

        return response()->json(['data' => $rows]);
    }

    public function show(string $id): JsonResponse
    {
        $org = $this->business->businessId();
        $sale = PosSaleModel::query()
            ->where('id', $id)
            ->where('organization_id', $org)
            ->first();

        if ($sale === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $lines = PosSaleLineModel::query()
            ->where('pos_sale_id', $sale->id)
            ->get()
            ->map(function (PosSaleLineModel $l) {
                $name = null;
                $sku = null;
                try {
                    $p = DB::table('catalog_products')->where('id', $l->product_id)->first();
                    $name = $p->name ?? null;
                    $sku = $p->sku ?? null;
                } catch (\Throwable) {
                }

                return [
                    'id' => $l->id,
                    'product_id' => $l->product_id,
                    'product_name' => $name,
                    'sku' => $sku,
                    'barcode' => $l->barcode,
                    'quantity' => $l->quantity,
                    'unit_price_amount' => $l->unit_price_amount,
                    'line_total_amount' => $l->line_total_amount,
                ];
            });

        return response()->json([
            'data' => [
                'id' => $sale->id,
                'document_number' => $sale->document_number,
                'pos_session_id' => $sale->pos_session_id,
                'warehouse_id' => $sale->warehouse_id,
                'customer_id' => $sale->customer_id,
                'subtotal_amount' => $sale->subtotal_amount,
                'discount_amount' => $sale->discount_amount,
                'total_amount' => $sale->total_amount,
                'currency' => $sale->currency,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'sold_at' => $sale->sold_at?->toIso8601String(),
                'lines' => $lines,
            ],
        ]);
    }
}
