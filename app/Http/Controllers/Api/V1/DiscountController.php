<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    /**
     * Get all discounts
     * 
     * @route GET /api/v1/discounts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $active = $request->input('active');

            $query = Discount::where('tenant_id', $user->tenant_id);
            
            if ($active !== null) {
                $query->where('is_active', $active === 'true');
            }

            $discounts = $query->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Discounts retrieved successfully',
                'data' => $discounts->items(),
                'pagination' => [
                    'total' => $discounts->total(),
                    'page' => $discounts->currentPage(),
                    'per_page' => $discounts->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create discount
     * 
     * @route POST /api/v1/discounts
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:discounts,code',
            'discount_type' => 'required|string|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
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

            $discount = Discount::create([
                'tenant_id' => $user->tenant_id,
                'code' => strtoupper($request->input('code')),
                'discount_type' => $request->input('discount_type'),
                'discount_value' => $request->input('discount_value'),
                'valid_from' => $request->input('valid_from'),
                'valid_until' => $request->input('valid_until'),
                'max_uses' => $request->input('max_uses'),
                'is_active' => $request->input('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discount created successfully',
                'data' => $discount,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get discount by ID
     * 
     * @route GET /api/v1/discounts/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $discount = Discount::where('tenant_id', $user->tenant_id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Discount retrieved successfully',
                'data' => $discount,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Discount not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update discount
     * 
     * @route PUT /api/v1/discounts/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
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

            $discount = Discount::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $discount->update($request->only('discount_type', 'discount_value', 'valid_from', 'valid_until', 'max_uses', 'is_active'));

            return response()->json([
                'success' => true,
                'message' => 'Discount updated successfully',
                'data' => $discount,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Discount not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete discount
     * 
     * @route DELETE /api/v1/discounts/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $discount = Discount::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $discount->delete();

            return response()->json([
                'success' => true,
                'message' => 'Discount deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Discount not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
