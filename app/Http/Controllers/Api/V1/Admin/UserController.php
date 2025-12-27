<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('preferences');

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Update user role
     */
    public function updateRole(UpdateUserRoleRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent changing own role
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot change your own role',
            ], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'data' => new UserResource($user->fresh('preferences')),
            'message' => 'User role updated successfully',
        ]);
    }

    /**
     * Suspend user
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent suspending own account
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot suspend your own account',
            ], 422);
        }

        $user->update(['status' => 'suspended']);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'data' => new UserResource($user->fresh('preferences')),
            'message' => 'User suspended successfully',
        ]);
    }

    /**
     * Activate user
     */
    public function activate(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);

        return response()->json([
            'data' => new UserResource($user->fresh('preferences')),
            'message' => 'User activated successfully',
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deleting own account
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        // Soft delete
        $user->update(['status' => 'deleted']);
        $user->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
