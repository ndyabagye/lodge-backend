<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('preferences');

        // Apply filters
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginatedResponse($users, UserResource::class);
    }

    /**
     * Update user role
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        // Prevent self-demotion
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot change your own role', 422);
        }

        $user->update([
            'role' => UserRole::from($request->role)
        ]);

        return $this->resourceResponse(
            new UserResource($user->fresh()),
            'User role updated successfully'
        );
    }

    /**
     * Suspend user
     */
    public function suspend(Request $request, User $user): JsonResponse
    {
        // Prevent self-suspension
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot suspend your own account', 422);
        }

        $user->update([
            'status' => UserStatus::SUSPENDED
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        return $this->resourceResponse(
            new UserResource($user->fresh()),
            'User suspended successfully'
        );
    }

    /**
     * Activate user
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        $user->update([
            'status' => UserStatus::ACTIVE
        ]);

        return $this->resourceResponse(
            new UserResource($user->fresh()),
            'User activated successfully'
        );
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot delete your own account', 422);
        }

        // Check for active bookings
        $activeBookings = $user->bookings()->active()->count();

        if ($activeBookings > 0) {
            return $this->errorResponse(
                'Cannot delete user with active bookings',
                422
            );
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
