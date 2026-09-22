<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\PengPos;
use App\Livewire\Pos;
use App\Livewire\Products;
use App\Livewire\Reports;
use App\Livewire\Settings;
use App\Livewire\Transactions;
use App\Models\Product;
use App\Models\Sale;
use App\Services\BarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->get('/login', Login::class)->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/pos', Pos::class)->name('pos');
    Route::get('/pengpos', PengPos::class)->name('pengpos');
    Route::get('/products', Products::class)->name('products');
    Route::get('/products/barcodes/pdf', function (Request $request) {
        $productIds = collect($request->input('ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->whereIn('id', $productIds)
            ->get()
            ->filter(fn (Product $product): bool => (bool) $product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path));

        abort_if($products->isEmpty(), 404, 'No generated barcode images were found.');

        $barcodeProducts = $products->map(fn (Product $product): array => [
            'image' => 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($product->barcode_image_path)),
            'sku' => $product->sku,
            'name' => $product->name,
        ])->values();

        return Pdf::loadView('products.barcodes-pdf', ['products' => $barcodeProducts])
            ->setPaper('a4')
            ->download($products->count() === 1
                ? 'barcode-'.$products->first()->sku.'.pdf'
                : 'barcodes-'.now()->format('Y-m-d-His').'.pdf');
    })->name('products.barcodes-pdf');
    Route::get('/products/barcodes/images', function (Request $request, BarcodeService $barcodeService) {
        $productIds = collect($request->input('ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->whereIn('id', $productIds)
            ->get()
            ->filter(fn (Product $product): bool => (bool) $product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path));

        abort_if($products->isEmpty(), 404, 'No generated barcode images were found.');

        $zipPath = tempnam(sys_get_temp_dir(), 'barcodes-').'.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($products as $product) {
            $zip->addFromString(($product->sku ?: $product->barcode).'.png', $barcodeService->labeledBarcodeImage($product));
        }
        $zip->close();

        return response()->download($zipPath, 'barcodes-'.now()->format('Y-m-d-His').'.zip')->deleteFileAfterSend(true);
    })->name('products.barcodes-images');
    
    Route::get('/products/labels/pdf', function (Request $request, BarcodeService $barcodeService) {
        $productIds = collect($request->input('ids', []))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->whereIn('id', $productIds)
            ->orderBy('name')
            ->get();

        abort_if($products->isEmpty(), 404, 'No active products were selected.');

        $labelProducts = $products->map(fn (Product $product): array => [
            'name' => $product->name,
            'price' => $product->selling_price,
            'barcode' => $product->barcode_image_path
                && Storage::disk('local')->exists($product->barcode_image_path)
                ? 'data:image/png;base64,'.base64_encode($barcodeService->labeledBarcodeImage($product))
                : null,
        ])->values();

        return Pdf::loadView('products.labels-pdf', ['products' => $labelProducts])
            ->setPaper([0, 0, 113.39, 85.04])
            ->download($products->count() === 1
                ? 'product-label-'.$products->first()->sku.'.pdf'
                : 'product-labels-'.now()->format('Y-m-d-His').'.pdf');
    })->name('products.labels-pdf');

    Route::get('/products/{product}/barcode-image', function (Product $product) {
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 404);
        abort_unless($product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path), 404);

        $image = app(BarcodeService::class)->labeledBarcodeImage($product);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    })->name('products.barcode-image');
    Route::get('/reports', Reports::class)->name('reports');
    Route::get('/transactions', Transactions::class)->name('transactions');
    
    Route::get('/transactions/{sale}/receipt', function (Sale $sale) {
        abort_unless($sale->tenant_id === auth()->user()->tenant_id, 404);

        $sale->load('items.product', 'user', 'location');
        $itemLines = $sale->items->sum(function ($item): int {
            $nameLength = strlen($item->product?->name ?? 'Product');

            return max(1, (int) ceil($nameLength / 18));
        });
        $receiptHeightMm = 86 + ($itemLines * 9);
        $receiptHeightPoints = $receiptHeightMm * 2.83465;

        return Pdf::loadView('sales.receipt-pdf', ['sale' => $sale])
            ->setPaper([0, 0, 226.77, $receiptHeightPoints])
            ->download('receipt-'.$sale->id.'.pdf');
    })->name('sales.receipt');

    Route::get('/settings', Settings::class)->name('settings');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});