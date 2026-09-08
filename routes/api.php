<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\BarcodeController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/**
 * API V1 Routes
 * Base URL: /api/v1
 * 
 * All protected routes require:
 * - auth:api middleware (JWT token in Authorization header)
 * - tenant.scoped middleware (auto-scopes queries to authenticated tenant)
 */

Route::prefix('v1')->group(function () {
    /**
     * ═══════════════════════════════════════════════════════════
     * PUBLIC ROUTES (No Authentication Required)
     * ═══════════════════════════════════════════════════════════
     */

    /**
     * Authentication Routes (Public)
     */
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    /**
     * ═══════════════════════════════════════════════════════════
     * PROTECTED ROUTES (Require JWT Authentication)
     * ═══════════════════════════════════════════════════════════
     */
    Route::middleware(['auth:api', 'tenant.scoped'])->group(function () {
        /**
         * Authentication Routes (Protected)
         */
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        });

        /**
         * Products Routes
         * Features:
         * - Auto-barcode generation on creation
         * - Cached search with filters
         * - Stock information included
         * - POS barcode lookup
         */
        Route::apiResource('products', ProductController::class);
        Route::get('products/barcode/{barcode}', [ProductController::class, 'getByBarcode'])->name('products.barcode');

        /**
         * Barcodes Routes
         * Features:
         * - Image export (base64 PNG)
         * - Manual generation
         * - Barcode validation
         * - Label printing (Avery 5160 format)
         */
        Route::prefix('barcodes')->group(function () {
            Route::get('/{product_id}/image', [BarcodeController::class, 'getImage'])->name('barcodes.image');
            Route::post('/generate', [BarcodeController::class, 'generate'])->name('barcodes.generate');
            Route::post('/{product_id}/regenerate', [BarcodeController::class, 'regenerate'])->name('barcodes.regenerate');
            Route::post('/print-labels', [BarcodeController::class, 'printLabels'])->name('barcodes.print');
            Route::get('/validate/{barcode}', [BarcodeController::class, 'validate'])->name('barcodes.validate');
        });

        /**
         * Sales/POS Routes
         * Features:
         * - Complete transaction with discount
         * - Automatic stock decrement
         * - Payment method tracking
         * - Date range filtering
         */
        Route::apiResource('sales', SaleController::class);

        /**
         * Inventory Routes
         * Features:
         * - Stock summary by location
         * - Adjustments (purchase, sale, damage, return)
         * - Low-stock alerts
         * - Audit trail with user tracking
         */
        Route::prefix('inventory')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
            Route::post('/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
            Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.lowStock');
            Route::get('/logs/{product_id}', [InventoryController::class, 'logs'])->name('inventory.logs');
        });

        /**
         * Reports Routes
         * Features:
         * - Daily sales by payment method
         * - Weekly trend analysis
         * - Monthly reports with top products
         * - Dashboard summary
         * - All cached for performance
         */
        Route::prefix('reports')->group(function () {
            Route::get('/daily', [ReportController::class, 'daily'])->name('reports.daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('reports.weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
            Route::get('/summary', [ReportController::class, 'summary'])->name('reports.summary');
        });

        /**
         * Locations Routes (Warehouses/Stores)
         * Features:
         * - Multi-location inventory tracking
         * - Inventory count per location
         * - Location-based sales
         */
        Route::apiResource('locations', LocationController::class);

        /**
         * Categories Routes
         * Features:
         * - Product categorization
         * - Category-based filtering
         */
        Route::apiResource('categories', CategoryController::class);

        /**
         * Suppliers Routes
         * Features:
         * - Supplier contact information
         * - Purchase order history
         * - Supplier performance tracking
         */
        Route::apiResource('suppliers', SupplierController::class);

        /**
         * Purchase Orders Routes
         * Features:
         * - Create with multiple items
         * - Status tracking (pending, received, cancelled)
         * - Automatic stock increment on received
         * - Expected delivery date tracking
         */
        Route::apiResource('purchase-orders', PurchaseOrderController::class);

        /**
         * Discounts Routes
         * Features:
         * - Percentage or fixed amount
         * - Validity date range
         * - Usage limits
         * - Active/inactive toggle
         */
        Route::apiResource('discounts', DiscountController::class);

        /**
         * Users Routes (Admin Only)
         * Features:
         * - User management
         * - Role assignment (admin, manager, staff)
         * - Active/inactive status
         * - Delete protection (cannot delete own account)
         */
        Route::apiResource('users', UserController::class);
    });

    /**
     * Public Routes
     */
    Route::prefix('health')->group(function () {
        Route::get('/', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now(),
            ]);
        })->name('health.check');
    });
});
