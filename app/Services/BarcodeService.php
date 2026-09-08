<?php

namespace App\Services;

use App\Models\Product;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Storage;

class BarcodeService
{
    protected BarcodeGeneratorPNG $generatorPNG;

    public function __construct()
    {
        $this->generatorPNG = new BarcodeGeneratorPNG();
    }

    /**
     * Generate barcode for a product
     * Auto-generates on product creation
     */
    public function generateBarcode(Product $product, ?string $barcodeValue = null): array
    {
        // SKU is the stable, scanner-friendly source value for product barcodes.
        if (!$barcodeValue) {
            $barcodeValue = $product->sku;
        }

        if (!$barcodeValue) {
            throw new \InvalidArgumentException('A SKU is required before generating a barcode.');
        }

        // Validate barcode format
        if (!$this->validateBarcodeFormat($barcodeValue, 'CODE128')) {
            throw new \InvalidArgumentException('The SKU contains characters that are not valid for a CODE128 barcode.');
        }

        // Create barcode directory
        $barcodeDir = "barcodes/{$product->tenant_id}";
        if (!Storage::disk('local')->exists($barcodeDir)) {
            Storage::disk('local')->makeDirectory($barcodeDir);
        }

        // Generate barcode image (PNG)
        $barcodePath = "{$barcodeDir}/product_{$product->id}.png";
        $barcodeImage = $this->generatorPNG->getBarcode(
            $barcodeValue,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            50
        );

        Storage::disk('local')->put($barcodePath, $barcodeImage);

        return [
            'barcode' => $barcodeValue,
            'barcode_format' => 'CODE128',
            'barcode_image_path' => $barcodePath,
        ];
    }

    /**
     * Validate barcode format
     */
    public function validateBarcodeFormat(string $barcode, string $format = 'CODE128'): bool
    {
        // CODE128 accepts most printable ASCII characters
        if ($format === 'CODE128') {
            return preg_match('/^[a-zA-Z0-9\-\._\s]{1,}$/', $barcode) === 1;
        }
        return false;
    }

    /**
     * Check if barcode is unique within tenant
     */
    public function isBarcodeUnique(string $barcode, int $tenantId, ?int $excludeProductId = null): bool
    {
        $query = Product::where('tenant_id', $tenantId)->where('barcode', $barcode);
        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }
        return $query->count() === 0;
    }

    /**
     * Get product by barcode (fast POS lookup)
     */
    public function getProductByBarcode(string $barcode, int $tenantId): ?Product
    {
        return Product::where('tenant_id', $tenantId)
            ->where('barcode', $barcode)
            ->first();
    }

    /**
     * Regenerate barcode for existing product
     */
    public function regenerateBarcode(Product $product, ?string $newBarcodeValue = null): array
    {
        // Delete old barcode image if exists
        if ($product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path)) {
            Storage::disk('local')->delete($product->barcode_image_path);
        }

        // Generate new barcode
        return $this->generateBarcode($product, $newBarcodeValue);
    }

    public function labeledBarcodeImage(Product $product): string
    {
        abort_unless($product->barcode_image_path && Storage::disk('local')->exists($product->barcode_image_path), 404);

        $barcode = imagecreatefromstring(Storage::disk('local')->get($product->barcode_image_path));
        $width = imagesx($barcode);
        $height = imagesy($barcode);
        $canvas = imagecreatetruecolor($width, $height + 28);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);

        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $barcode, 0, 0, 0, 0, $width, $height);
        $label = (string) ($product->sku ?: $product->barcode);
        imagestring($canvas, 3, max(4, (int) (($width - strlen($label) * 8) / 2)), $height + 7, $label, $black);

        ob_start();
        imagepng($canvas);
        $image = ob_get_clean();
        imagedestroy($barcode);
        imagedestroy($canvas);

        return $image;
    }

    /**
     * Generate HTML for barcode labels (Avery 5160 2x1" format)
     */
    public function generateBarcodeLabels(array $productIds): string
    {
        $products = Product::whereIn('id', $productIds)->get();

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
                .label { 
                    width: 2in; 
                    height: 1in; 
                    display: inline-block; 
                    margin: 0.125in;
                    border: 1px dashed #ccc;
                    padding: 0.1in;
                    text-align: center;
                    page-break-inside: avoid;
                    float: left;
                }
                .barcode-image { max-width: 100%; max-height: 0.7in; margin: 0.05in auto; }
                .product-name { font-size: 8px; font-weight: bold; margin: 0.05in 0; overflow: hidden; }
                .product-sku { font-size: 7px; color: #666; margin: 0.05in 0; }
            </style>
        </head>
        <body>';

        foreach ($products as $product) {
            if (!$product->barcode_image_path) {
                continue;
            }

            $imagePath = Storage::disk('local')->path($product->barcode_image_path);
            if (!file_exists($imagePath)) {
                continue;
            }

            $base64Image = base64_encode(file_get_contents($imagePath));
            $imageData = 'data:image/png;base64,' . $base64Image;

            $html .= '
            <div class="label">
                <div class="product-name">' . htmlspecialchars(substr($product->name, 0, 15)) . '</div>
                <img src="' . $imageData . '" class="barcode-image" alt="' . htmlspecialchars($product->barcode) . '">
                <div class="product-sku">' . htmlspecialchars($product->sku ?? $product->barcode) . '</div>
            </div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Batch generate barcodes for multiple products
     */
    public function batchGenerateBarcodes(array $productIds, int $tenantId): array
    {
        $products = Product::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get();

        $results = [];

        foreach ($products as $product) {
            if ($product->sku) {
                $barcodeData = $product->barcode
                    ? $this->regenerateBarcode($product)
                    : $this->generateBarcode($product);
                $product->update($barcodeData);
                $results[$product->id] = [
                    'success' => true,
                    'barcode' => $barcodeData['barcode'],
                    'message' => 'Barcode generated successfully',
                ];
            } else {
                $results[$product->id] = [
                    'success' => false,
                    'message' => 'Product already has a barcode',
                ];
            }
        }

        return $results;
    }
}