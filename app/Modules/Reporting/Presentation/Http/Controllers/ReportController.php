<?php

declare(strict_types=1);

namespace StoreYar\Modules\Reporting\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use StoreYar\Shared\Application\Context\BusinessContext;

/**
 * Read-only operational reports against live domain tables.
 */
final class ReportController
{
    public function __construct(
        private BusinessContext $business,
    ) {}

    public function dashboard(): JsonResponse
    {
        $org = $this->business->businessId();

        $productCount = DB::table('catalog_products')->where('organization_id', $org)->count();
        $customerCount = DB::table('customers')->where('organization_id', $org)->count();
        $supplierCount = DB::table('suppliers')->where('organization_id', $org)->count();
        $warehouseCount = DB::table('inventory_warehouses')->where('organization_id', $org)->where('is_active', true)->count();

        $stockQty = (int) DB::table('inventory_stock_items')->where('organization_id', $org)->sum('quantity');
        $stockSku = DB::table('inventory_stock_items')->where('organization_id', $org)->where('quantity', '>', 0)->count();

        $today = now()->toDateString();
        $posToday = DB::table('pos_sales')
            ->where('organization_id', $org)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total')
            ->first();

        $openPos = DB::table('pos_sessions')
            ->where('organization_id', $org)
            ->where('status', 'OPEN')
            ->count();

        $openPo = DB::table('purchase_orders')
            ->where('organization_id', $org)
            ->whereIn('status', ['ORDERED', 'PARTIAL'])
            ->count();

        $lowStock = DB::table('inventory_stock_items as s')
            ->join('catalog_products as p', 'p.id', '=', 's.product_id')
            ->where('s.organization_id', $org)
            ->whereNotNull('p.min_stock')
            ->whereColumn('s.quantity', '<', 'p.min_stock')
            ->count();

        return response()->json([
            'data' => [
                'products' => $productCount,
                'customers' => $customerCount,
                'suppliers' => $supplierCount,
                'warehouses' => $warehouseCount,
                'stock_units' => $stockQty,
                'stock_skus_with_qty' => $stockSku,
                'pos_sales_today_count' => (int) ($posToday->cnt ?? 0),
                'pos_sales_today_amount' => (int) ($posToday->total ?? 0),
                'open_pos_sessions' => $openPos,
                'open_purchase_orders' => $openPo,
                'low_stock_lines' => $lowStock,
            ],
        ]);
    }

    public function stockOnHand(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $q = DB::table('inventory_stock_items as s')
            ->leftJoin('catalog_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('inventory_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.organization_id', $org)
            ->select([
                's.id as stock_item_id',
                's.warehouse_id',
                'w.code as warehouse_code',
                'w.name as warehouse_name',
                's.product_id',
                'p.sku',
                'p.name as product_name',
                's.quantity',
                's.reserved_quantity',
                DB::raw('(s.quantity) as available'),
                'p.min_stock',
                'p.reorder_point',
            ])
            ->orderBy('w.code')
            ->orderBy('p.sku');

        if ($request->query('warehouse_id')) {
            $q->where('s.warehouse_id', $request->query('warehouse_id'));
        }
        if ($request->query('product_id')) {
            $q->where('s.product_id', $request->query('product_id'));
        }
        if ($request->boolean('only_positive')) {
            $q->where('s.quantity', '>', 0);
        }

        $rows = $q->limit(500)->get();

        return response()->json(['data' => $rows]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $org = $this->business->businessId();

        $rows = DB::table('inventory_stock_items as s')
            ->join('catalog_products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('inventory_warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.organization_id', $org)
            ->where(function ($q): void {
                $q->where(function ($q2): void {
                    $q2->whereNotNull('p.min_stock')
                        ->whereColumn('s.quantity', '<', 'p.min_stock');
                })->orWhere(function ($q2): void {
                    $q2->whereNotNull('p.reorder_point')
                        ->whereColumn('s.quantity', '<=', 'p.reorder_point');
                });
            })
            ->select([
                's.warehouse_id',
                'w.code as warehouse_code',
                's.product_id',
                'p.sku',
                'p.name as product_name',
                's.quantity',
                'p.min_stock',
                'p.reorder_point',
                'p.reorder_qty',
            ])
            ->orderBy('s.quantity')
            ->limit(200)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $byDay = DB::table('pos_sales')
            ->where('organization_id', $org)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->selectRaw('DATE(sold_at) as day, COUNT(*) as sales_count, COALESCE(SUM(subtotal_amount),0) as subtotal, COALESCE(SUM(discount_amount),0) as discount, COALESCE(SUM(total_amount),0) as total')
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('day')
            ->get();

        $byPayment = DB::table('pos_sales')
            ->where('organization_id', $org)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->selectRaw('payment_method, COUNT(*) as sales_count, COALESCE(SUM(total_amount),0) as total')
            ->groupBy('payment_method')
            ->get();

        $totals = DB::table('pos_sales')
            ->where('organization_id', $org)
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', '>=', $from)
            ->whereDate('sold_at', '<=', $to)
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(subtotal_amount),0) as subtotal, COALESCE(SUM(discount_amount),0) as discount, COALESCE(SUM(total_amount),0) as total')
            ->first();

        $topProducts = DB::table('pos_sale_lines as l')
            ->join('pos_sales as s', 's.id', '=', 'l.pos_sale_id')
            ->leftJoin('catalog_products as p', 'p.id', '=', 'l.product_id')
            ->where('s.organization_id', $org)
            ->where('s.status', 'COMPLETED')
            ->whereDate('s.sold_at', '>=', $from)
            ->whereDate('s.sold_at', '<=', $to)
            ->selectRaw('l.product_id, p.sku, p.name, SUM(l.quantity) as qty, SUM(l.line_total_amount) as amount')
            ->groupBy('l.product_id', 'p.sku', 'p.name')
            ->orderByDesc('amount')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'totals' => $totals,
                'by_day' => $byDay,
                'by_payment_method' => $byPayment,
                'top_products' => $topProducts,
            ],
        ]);
    }

    public function purchaseSummary(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $orders = DB::table('purchase_orders')
            ->where('organization_id', $org)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total')
            ->groupBy('status')
            ->get();

        $receipts = DB::table('goods_receipts')
            ->where('organization_id', $org)
            ->where('status', 'POSTED')
            ->whereDate('received_at', '>=', $from)
            ->whereDate('received_at', '<=', $to)
            ->selectRaw('COUNT(*) as cnt')
            ->first();

        $bySupplier = DB::table('purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('po.organization_id', $org)
            ->whereDate('po.created_at', '>=', $from)
            ->whereDate('po.created_at', '<=', $to)
            ->selectRaw('po.supplier_id, s.code as supplier_code, s.name as supplier_name, COUNT(*) as orders_count, COALESCE(SUM(po.total_amount),0) as total')
            ->groupBy('po.supplier_id', 's.code', 's.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'orders_by_status' => $orders,
                'goods_receipts_count' => (int) ($receipts->cnt ?? 0),
                'by_supplier' => $bySupplier,
            ],
        ]);
    }

    public function stockMovements(Request $request): JsonResponse
    {
        $org = $this->business->businessId();
        $from = $request->query('from', now()->subDays(7)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $q = DB::table('inventory_stock_movements as m')
            ->leftJoin('catalog_products as p', 'p.id', '=', 'm.product_id')
            ->leftJoin('inventory_warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->where('m.organization_id', $org)
            ->whereDate('m.occurred_at', '>=', $from)
            ->whereDate('m.occurred_at', '<=', $to)
            ->select([
                'm.id',
                'm.warehouse_id',
                'w.code as warehouse_code',
                'm.product_id',
                'p.sku',
                'p.name as product_name',
                'm.movement_type',
                'm.document_type',
                'm.document_id',
                'm.direction',
                'm.quantity_delta',
                'm.quantity_after',
                'm.reason',
                'm.occurred_at',
            ])
            ->orderByDesc('m.occurred_at')
            ->limit(200);

        if ($request->query('warehouse_id')) {
            $q->where('m.warehouse_id', $request->query('warehouse_id'));
        }
        if ($request->query('product_id')) {
            $q->where('m.product_id', $request->query('product_id'));
        }
        if ($request->query('movement_type')) {
            $q->where('m.movement_type', $request->query('movement_type'));
        }

        return response()->json(['data' => $q->get()]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $org = $this->business->businessId();

        $rows = DB::table('accounting_journal_lines as l')
            ->join('accounting_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.organization_id', $org)
            ->where('e.status', 'POSTED')
            ->selectRaw('a.id as account_id, a.code, a.name, a.type, COALESCE(SUM(l.debit_amount),0) as total_debit, COALESCE(SUM(l.credit_amount),0) as total_credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->get()
            ->map(static function ($r) {
                $debit = (int) $r->total_debit;
                $credit = (int) $r->total_credit;
                // simplified balance view: assets/expenses natural debit
                $balance = $debit - $credit;

                return [
                    'account_id' => $r->account_id,
                    'code' => $r->code,
                    'name' => $r->name,
                    'type' => $r->type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance' => $balance,
                ];
            });

        $sumDebit = $rows->sum('total_debit');
        $sumCredit = $rows->sum('total_credit');

        return response()->json([
            'data' => [
                'accounts' => $rows->values(),
                'totals' => [
                    'debit' => $sumDebit,
                    'credit' => $sumCredit,
                    'balanced' => $sumDebit === $sumCredit,
                ],
            ],
        ]);
    }

    public function posSessionReport(string $id): JsonResponse
    {
        $org = $this->business->businessId();

        $session = DB::table('pos_sessions')
            ->where('id', $id)
            ->where('organization_id', $org)
            ->first();

        if ($session === null) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $sales = DB::table('pos_sales')
            ->where('pos_session_id', $id)
            ->where('status', 'COMPLETED')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total, payment_method')
            ->groupBy('payment_method')
            ->get();

        $grand = DB::table('pos_sales')
            ->where('pos_session_id', $id)
            ->where('status', 'COMPLETED')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(discount_amount),0) as discount')
            ->first();

        $cashSales = (int) DB::table('pos_sales')
            ->where('pos_session_id', $id)
            ->where('status', 'COMPLETED')
            ->where('payment_method', 'CASH')
            ->sum('total_amount');

        $expectedCash = (int) $session->opening_float_amount + $cashSales;

        return response()->json([
            'data' => [
                'session' => $session,
                'sales_by_payment' => $sales,
                'totals' => $grand,
                'cash' => [
                    'opening_float' => (int) $session->opening_float_amount,
                    'cash_sales' => $cashSales,
                    'expected_cash' => $expectedCash,
                    'closing_count' => $session->closing_count_amount,
                    'variance' => $session->variance_amount,
                ],
            ],
        ]);
    }
}
