<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users for tenant (admin only)
     * 
     * @route GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $users = User::where('tenant_id', $user->tenant_id)
                ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Create new user (admin only)
     * 
     * @route POST /api/v1/users
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,manager,staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $authUser = auth('api')->user();
            if (!$authUser->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $user = User::create([
                'tenant_id' => $authUser->tenant_id,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $request->input('role'),
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->only('id', 'name', 'email', 'role', 'is_active'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get user by ID
     * 
     * @route GET /api/v1/users/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $authUser = auth('api')->user();
            if (!$authUser->isAdmin() && $authUser->id != $id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $user = User::where('tenant_id', $authUser->tenant_id)
                ->select('id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update user
     * 
     * @route PUT /api/v1/users/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|in:admin,manager,staff',
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
            $authUser = auth('api')->user();
            if (!$authUser->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $user = User::where('tenant_id', $authUser->tenant_id)->findOrFail($id);
            $user->update($request->only('name', 'role', 'is_active'));

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->only('id', 'name', 'email', 'role', 'is_active'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete user (admin only)
     * 
     * @route DELETE /api/v1/users/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $authUser = auth('api')->user();
            if (!$authUser->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($authUser->id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own user account',
                ], 400);
            }

            $user = User::where('tenant_id', $authUser->tenant_id)->findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
