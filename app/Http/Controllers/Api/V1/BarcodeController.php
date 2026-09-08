<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BarcodeController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Get barcode image for product
     * 
     * @route GET /api/v1/barcodes/{product_id}/image
     */
    public function getImage(Request $request, int $productId): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($productId);

            if (!$product->barcode_image_path || !Storage::disk('local')->exists($product->barcode_image_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode image not found',
                ], 404);
            }

            $imagePath = Storage::disk('local')->path($product->barcode_image_path);
            $imageData = base64_encode(file_get_contents($imagePath));

            return response()->json([
                'success' => true,
                'message' => 'Barcode image retrieved successfully',
                'data' => [
                    'image' => 'data:image/png;base64,' . $imageData,
                    'barcode' => $product->barcode,
                    'product_name' => $product->name,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate barcode for product (if not already generated)
     * 
     * @route POST /api/v1/barcodes/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth('api')->user();
            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($request->input('product_id'));

            if ($product->barcode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already has a barcode',
                ], 400);
            }

            $barcodeData = $this->barcodeService->generateBarcode($product);
            $product->update($barcodeData);

            return response()->json([
                'success' => true,
                'message' => 'Barcode generated successfully',
                'data' => [
                    'barcode' => $product->barcode,
                    'barcode_format' => $product->barcode_format,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Regenerate barcode for product
     * 
     * @route POST /api/v1/barcodes/{product_id}/regenerate
     */
    public function regenerate(Request $request, int $productId): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($productId);

            $barcodeData = $this->barcodeService->regenerateBarcode($product);
            $product->update($barcodeData);

            return response()->json([
                'success' => true,
                'message' => 'Barcode regenerated successfully',
                'data' => [
                    'barcode' => $product->barcode,
                    'barcode_format' => $product->barcode_format,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Print barcode labels for multiple products
     * Returns HTML for frontend PDF generation
     * 
     * @route POST /api/v1/barcodes/print-labels
     */
    public function printLabels(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth('api')->user();
            $productIds = $request->input('product_ids');

            // Verify all products belong to tenant
            $productCount = Product::where('tenant_id', $user->tenant_id)
                ->whereIn('id', $productIds)
                ->count();

            if ($productCount !== count($productIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some products not found or unauthorized',
                ], 403);
            }

            $htmlContent = $this->barcodeService->generateBarcodeLabels($productIds);

            return response()->json([
                'success' => true,
                'message' => 'Labels generated successfully',
                'data' => [
                    'html_content' => $htmlContent,
                    'product_count' => count($productIds),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate barcode format (public endpoint - no auth)
     * 
     * @route GET /api/v1/barcodes/validate/{barcode}
     */
    public function validate(Request $request, string $barcode): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $isValid = $this->barcodeService->validateBarcodeFormat($barcode);
            $isUnique = $this->barcodeService->isBarcodeUnique($barcode, $user->tenant_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid_format' => $isValid,
                    'is_unique' => $isUnique,
                    'message' => $isValid && $isUnique 
                        ? 'Barcode is valid and unique' 
                        : 'Barcode format invalid or not unique',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}