<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Get all suppliers
     * 
     * @route GET /api/v1/suppliers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $suppliers = Supplier::where('tenant_id', $user->tenant_id)
                ->withCount('purchaseOrders')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Suppliers retrieved successfully',
                'data' => $suppliers->items(),
                'pagination' => [
                    'total' => $suppliers->total(),
                    'page' => $suppliers->currentPage(),
                    'per_page' => $suppliers->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create supplier
     * 
     * @route POST /api/v1/suppliers
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
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
            if (!$user->isManager()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $supplier = Supplier::create([
                'tenant_id' => $user->tenant_id,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'country' => $request->input('country'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully',
                'data' => $supplier,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get supplier by ID
     * 
     * @route GET /api/v1/suppliers/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $supplier = Supplier::where('tenant_id', $user->tenant_id)
                ->with('purchaseOrders')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Supplier retrieved successfully',
                'data' => $supplier,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update supplier
     * 
     * @route PUT /api/v1/suppliers/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'sometimes|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
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
            if (!$user->isManager()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $supplier = Supplier::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $supplier->update($request->only('name', 'email', 'phone', 'address', 'city', 'country'));

            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully',
                'data' => $supplier,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete supplier
     * 
     * @route DELETE /api/v1/suppliers/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $supplier = Supplier::where('tenant_id', $user->tenant_id)->findOrFail($id);

            if ($supplier->purchaseOrders()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with active purchase orders',
                ], 409);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Supplier not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
