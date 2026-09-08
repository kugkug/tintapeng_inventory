<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\BarcodeService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected ProductService $productService;
    protected BarcodeService $barcodeService;

    public function __construct(ProductService $productService, BarcodeService $barcodeService)
    {
        $this->productService = $productService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * List all products with search and pagination
     * 
     * @route GET /api/v1/products?query=name&page=1
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $query = $request->input('query', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            $result = $this->productService->searchProducts($user->tenant_id, $query, $page, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $result['items'],
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
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
     * Create new product with auto-generated barcode
     * 
     * @route POST /api/v1/products
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._\-\s]+$/',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('tenant_id', auth('api')->user()?->tenant_id)),
            ],
            'category_id' => 'required|exists:categories,id',
            'cost_per_unit' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
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

            // Check authorization - only manager+ can create
            if (!$user->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to create products',
                ], 403);
            }

            // Create product
            $product = Product::create([
                'tenant_id' => $user->tenant_id,
                'name' => $request->input('name'),
                'sku' => $request->input('sku'),
                'category_id' => $request->input('category_id'),
                'cost_per_unit' => $request->input('cost_per_unit'),
                'selling_price' => $request->input('selling_price'),
                'description' => $request->input('description'),
            ]);

            // Auto-generate barcode on product creation (core feature)
            $barcodeData = $this->barcodeService->generateBarcode($product);
            $product->update($barcodeData);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully with auto-generated barcode',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get product by ID with stock info
     * 
     * @route GET /api/v1/products/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $productData = $this->productService->getProductWithStock($id, $user->tenant_id);

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => $productData,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
     * Get product by barcode (fast POS lookup)
     * 
     * @route GET /api/v1/products/barcode/{barcode}
     */
    public function getByBarcode(Request $request, string $barcode): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $product = $this->barcodeService->getProductByBarcode($barcode, $user->tenant_id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $productData = $this->productService->getProductWithStock($product->id, $user->tenant_id);

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved by barcode',
                'data' => $productData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update product
     * 
     * @route PUT /api/v1/products/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9._\-\s]+$/',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('tenant_id', auth('api')->user()?->tenant_id))
                    ->ignore($id),
            ],
            'category_id' => 'sometimes|exists:categories,id',
            'cost_per_unit' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
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

            // Check authorization
            if (!$user->isManager()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update products',
                ], 403);
            }

            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $skuChanged = $request->filled('sku') && $request->input('sku') !== $product->sku;
            $product->update($request->only('name', 'sku', 'category_id', 'cost_per_unit', 'selling_price', 'description'));

            if ($skuChanged) {
                $product->update($this->barcodeService->regenerateBarcode($product));
            }

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
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
     * Delete product
     * 
     * @route DELETE /api/v1/products/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();

            // Check authorization - only admin
            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete products',
                ], 403);
            }

            $product = Product::where('tenant_id', $user->tenant_id)->findOrFail($id);

            // Delete barcode image if exists
            if ($product->barcode_image_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($product->barcode_image_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($product->barcode_image_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
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
}
