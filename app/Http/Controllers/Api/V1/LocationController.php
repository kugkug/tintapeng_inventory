<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Get all locations for tenant
     * 
     * @route GET /api/v1/locations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $locations = Location::where('tenant_id', $user->tenant_id)
                ->withCount('inventoryItems')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Locations retrieved successfully',
                'data' => $locations->items(),
                'pagination' => [
                    'total' => $locations->total(),
                    'page' => $locations->currentPage(),
                    'per_page' => $locations->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create location
     * 
     * @route POST /api/v1/locations
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
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

            $location = Location::create([
                'tenant_id' => $user->tenant_id,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'address' => $request->input('address'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => $location,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get location by ID
     * 
     * @route GET /api/v1/locations/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $location = Location::where('tenant_id', $user->tenant_id)
                ->with('inventoryItems.product')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Location retrieved successfully',
                'data' => $location,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update location
     * 
     * @route PUT /api/v1/locations/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
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

            $location = Location::where('tenant_id', $user->tenant_id)->findOrFail($id);
            $location->update($request->only('name', 'description', 'address'));

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => $location,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete location
     * 
     * @route DELETE /api/v1/locations/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $location = Location::where('tenant_id', $user->tenant_id)->findOrFail($id);

            // Check if location has inventory items
            if ($location->inventoryItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location with inventory items',
                ], 409);
            }

            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Location not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
