<?php

declare(strict_types=1);

namespace StoreYar\Modules\Reporting\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use StoreYar\Shared\Application\Context\BusinessContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant-scoped data export for backup / migration / BI.
 */
final class ExportController
{
    public function __construct(
        private BusinessContext $business,
    ) {}

    public function products(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $rows = DB::table('catalog_products')
            ->where('organization_id', $org)
            ->orderBy('sku')
            ->get([
                'id', 'sku', 'name', 'status', 'unit_price_amount', 'cost_price_amount',
                'currency', 'is_sellable', 'is_stockable', 'min_stock', 'reorder_point',
                'brand_id', 'base_unit_id', 'created_at', 'updated_at',
            ]);

        return $this->respond($request, 'products', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function barcodes(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $rows = DB::table('catalog_product_barcodes')
            ->where('organization_id', $org)
            ->orderBy('barcode')
            ->get(['id', 'product_id', 'barcode', 'type', 'is_primary', 'created_at']);

        return $this->respond($request, 'barcodes', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function stock(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $q = DB::table('inventory_stock_items as s')
            ->leftJoin('catalog_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('inventory_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.organization_id', $org)
            ->orderBy('w.code')
            ->orderBy('p.sku')
            ->select([
                's.id', 's.warehouse_id', 'w.code as warehouse_code', 's.product_id',
                'p.sku', 'p.name as product_name', 's.quantity', 's.reserved_quantity',
                's.version', 's.updated_at',
            ]);

        if ($request->query('warehouse_id')) {
            $q->where('s.warehouse_id', $request->query('warehouse_id'));
        }

        $rows = $q->get();

        return $this->respond($request, 'stock', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function customers(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $rows = DB::table('customers')
            ->where('organization_id', $org)
            ->orderBy('code')
            ->get([
                'id', 'code', 'name', 'phone', 'email', 'tax_id',
                'outstanding_balance_amount', 'credit_limit_amount', 'is_active', 'created_at',
            ]);

        return $this->respond($request, 'customers', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function suppliers(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $rows = DB::table('suppliers')
            ->where('organization_id', $org)
            ->orderBy('code')
            ->get([
                'id', 'code', 'name', 'phone', 'email', 'tax_id',
                'outstanding_balance_amount', 'is_active', 'created_at',
            ]);

        return $this->respond($request, 'suppliers', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function sales(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = DB::table('pos_sales')
            ->where('organization_id', $org)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->orderBy('sold_at')
            ->get([
                'id', 'document_number', 'pos_session_id', 'warehouse_id', 'customer_id',
                'subtotal_amount', 'discount_amount', 'total_amount', 'currency',
                'payment_method', 'sold_at', 'created_at',
            ]);

        return $this->respond($request, 'sales', $rows->map(fn ($r) => (array) $r)->all(), [
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function movements(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = DB::table('inventory_stock_movements')
            ->where('organization_id', $org)
            ->whereDate('occurred_at', '>=', $from)
            ->whereDate('occurred_at', '<=', $to)
            ->orderBy('occurred_at')
            ->limit(5000)
            ->get([
                'id', 'warehouse_id', 'product_id', 'movement_type', 'document_type',
                'document_id', 'direction', 'quantity_delta', 'quantity_after',
                'reason', 'occurred_at',
            ]);

        return $this->respond($request, 'movements', $rows->map(fn ($r) => (array) $r)->all(), [
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function journals(Request $request): JsonResponse|StreamedResponse
    {
        $org = $this->business->businessId();
        $rows = DB::table('accounting_journal_entries')
            ->where('organization_id', $org)
            ->where('status', 'POSTED')
            ->orderBy('entry_date')
            ->limit(2000)
            ->get([
                'id', 'document_number', 'entry_date', 'source_type', 'source_id',
                'memo', 'total_debit', 'total_credit', 'currency', 'posted_at',
            ]);

        return $this->respond($request, 'journals', $rows->map(fn ($r) => (array) $r)->all());
    }

    public function manifest(): JsonResponse
    {
        $org = $this->business->businessId();

        return response()->json([
            'data' => [
                'organization_id' => $org,
                'exported_at' => now()->toIso8601String(),
                'resources' => [
                    'products' => DB::table('catalog_products')->where('organization_id', $org)->count(),
                    'barcodes' => DB::table('catalog_product_barcodes')->where('organization_id', $org)->count(),
                    'stock_items' => DB::table('inventory_stock_items')->where('organization_id', $org)->count(),
                    'customers' => DB::table('customers')->where('organization_id', $org)->count(),
                    'suppliers' => DB::table('suppliers')->where('organization_id', $org)->count(),
                    'pos_sales' => DB::table('pos_sales')->where('organization_id', $org)->count(),
                    'stock_movements' => DB::table('inventory_stock_movements')->where('organization_id', $org)->count(),
                    'journal_entries' => DB::table('accounting_journal_entries')->where('organization_id', $org)->where('status', 'POSTED')->count(),
                ],
                'endpoints' => [
                    'GET /api/export/manifest',
                    'GET /api/export/products?format=json|csv',
                    'GET /api/export/barcodes?format=json|csv',
                    'GET /api/export/stock?format=json|csv',
                    'GET /api/export/customers?format=json|csv',
                    'GET /api/export/suppliers?format=json|csv',
                    'GET /api/export/sales?from=&to=&format=json|csv',
                    'GET /api/export/movements?from=&to=&format=json|csv',
                    'GET /api/export/journals?format=json|csv',
                ],
            ],
        ]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, mixed> $meta
     */
    private function respond(Request $request, string $name, array $rows, array $meta = []): JsonResponse|StreamedResponse
    {
        $format = strtolower((string) $request->query('format', 'json'));

        if ($format === 'csv') {
            return $this->csv($name, $rows);
        }

        return response()->json([
            'meta' => array_merge([
                'resource' => $name,
                'count' => count($rows),
                'exported_at' => now()->toIso8601String(),
            ], $meta),
            'data' => $rows,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function csv(string $name, array $rows): StreamedResponse
    {
        $filename = $name . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            if ($rows === []) {
                fputcsv($out, ['empty']);
                fclose($out);

                return;
            }
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, array_map(static function ($v) {
                    if ($v instanceof \DateTimeInterface) {
                        return $v->format('c');
                    }

                    return is_scalar($v) || $v === null ? $v : json_encode($v);
                }, $row));
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
