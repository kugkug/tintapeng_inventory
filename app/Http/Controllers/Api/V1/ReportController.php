<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get daily sales report
     * 
     * @route GET /api/v1/reports/daily?date=2026-08-25
     */
    public function daily(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $date = $request->input('date', now()->format('Y-m-d'));

            $cacheKey = $this->cacheService->getCacheKeyForSalesReport($user->tenant_id, $date);
            $cached = $this->cacheService->get($cacheKey);

            if ($cached) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daily report retrieved (cached)',
                    'data' => $cached,
                ], 200);
            }

            $sales = Sale::where('tenant_id', $user->tenant_id)
                ->whereDate('created_at', $date)
                ->selectRaw('
                    COUNT(*) as total_sales,
                    SUM(subtotal) as total_subtotal,
                    SUM(discount_amount) as total_discount,
                    SUM(total_amount) as total_revenue,
                    payment_method
                ')
                ->groupBy('payment_method')
                ->get();

            $summary = Sale::where('tenant_id', $user->tenant_id)
                ->whereDate('created_at', $date)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    COUNT(DISTINCT user_id) as total_users,
                    SUM(subtotal) as total_subtotal,
                    SUM(discount_amount) as total_discount,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_transaction
                ')
                ->first();

            $result = [
                'date' => $date,
                'summary' => $summary,
                'by_payment_method' => $sales,
            ];

            $this->cacheService->put($cacheKey, $result, 60);

            return response()->json([
                'success' => true,
                'message' => 'Daily report retrieved successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get weekly sales report
     * 
     * @route GET /api/v1/reports/weekly?start_date=2026-08-18&end_date=2026-08-24
     */
    public function weekly(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
            $endDate = $request->input('end_date', now()->format('Y-m-d'));

            $sales = Sale::where('tenant_id', $user->tenant_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as transactions,
                    SUM(total_amount) as revenue
                ')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get();

            $summary = Sale::where('tenant_id', $user->tenant_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_transaction,
                    MAX(total_amount) as max_transaction,
                    MIN(total_amount) as min_transaction
                ')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Weekly report retrieved successfully',
                'data' => [
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'summary' => $summary,
                    'daily_breakdown' => $sales,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get monthly sales report
     * 
     * @route GET /api/v1/reports/monthly?month=08&year=2026
     */
    public function monthly(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $sales = Sale::where('tenant_id', $user->tenant_id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as transactions,
                    SUM(total_amount) as revenue
                ')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get();

            $summary = Sale::where('tenant_id', $user->tenant_id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_transaction,
                    SUM(discount_amount) as total_discounts
                ')
                ->first();

            $topProducts = Sale::where('tenant_id', $user->tenant_id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->selectRaw('
                    products.id,
                    products.name,
                    products.sku,
                    SUM(sale_items.quantity) as total_sold,
                    SUM(sale_items.total_price) as total_revenue
                ')
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Monthly report retrieved successfully',
                'data' => [
                    'period' => ['month' => $month, 'year' => $year],
                    'summary' => $summary,
                    'daily_breakdown' => $sales,
                    'top_products' => $topProducts,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get sales summary/dashboard
     * 
     * @route GET /api/v1/reports/summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();

            $today = Sale::where('tenant_id', $user->tenant_id)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
                ->first();

            $thisMonth = Sale::where('tenant_id', $user->tenant_id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
                ->first();

            $allTime = Sale::where('tenant_id', $user->tenant_id)
                ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Sales summary retrieved successfully',
                'data' => [
                    'today' => $today,
                    'this_month' => $thisMonth,
                    'all_time' => $allTime,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
