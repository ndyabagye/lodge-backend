<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdatePreferencesRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->resourceResponse(
            new UserResource($request->user()->load('preferences')), 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->resourceResponse(
            new UserResource($user->fresh('preferences')),
            'Profile updated successfully'
        );
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens except current
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * Get user preferences
     */
    public function preferences(Request $request): JsonResponse
    {
        $preferences = $request->user()->preferences;

        if (! $preferences) {
            $preferences = $request->user()->preferences()->create([
                'email_notifications' => true,
                'sms_notifications' => false,
                'marketing_communications' => false,
            ]);
        }

        return $this->resourceResponse(new UserPreferenceResource($preferences), 200);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $preferences = $request->user()->preferences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );

        return $this->resourceResponse(
            new UserPreferenceResource($preferences),
            'Preferences updated successfully'
        );
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|current_password',
            'confirmation' => 'required|accepted',
        ]);

        $user = $request->user();

        // Soft delete
        $user->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return $this->successResponse(null, 'Account deleted successfully');
    }
}
