<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePreferencesRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\WelcomeUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => UserRole::GUEST,
            'status' => UserStatus::ACTIVE,
        ]);

        // Create default user preferences
        $user->preferences()->create([
            'email_notifications' => true,
            'sms_notifications' => false,
            'marketing_communications' => false,
        ]);

        event(new Registered($user));

        // Send welcome email
        $user->notify(new WelcomeUser($user));

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'data' => [
                'user' => new UserResource($user->load('preferences')),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        // Revoke old tokens (optional - enforce single session)
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user->load('preferences')),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('preferences')),
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email',
            ]);
        }

        return response()->json([
            'message' => 'Unable to send reset link',
        ], 500);
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token',
            ),
            function (User $user, string $password) {
                $user
                    ->forceFill([
                        'password' => Hash::make($password),
                    ])
                    ->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully',
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'uuid'],
            'hash' => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        if (! hash_equals((string) $request->hash, sha1($user->email))) {
            return response()->json(
                [
                    'message' => 'Invalid verification link',
                ],
                403,
            );
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('preferences')),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'data' => new UserResource($user->fresh('preferences')),
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens except current
        $request
            ->user()
            ->tokens()
            ->where('id', '!=', $request->user()->currentAccessToken()->id)
            ->delete();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $preferences =
            $request->user()->preferences ??
            UserPreference::create([
                'user_id' => $request->user()->id,
            ]);

        return response()->json([
            'data' => [
                'email_notifications' => $preferences->email_notifications,
                'sms_notifications' => $preferences->sms_notifications,
                'marketing_communications' => $preferences->marketing_communications,
            ],
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(
        UpdatePreferencesRequest $request,
    ): JsonResponse {
        $preferences = $request->user()->preferences;

        if (! $preferences) {
            $preferences = UserPreference::create([
                'user_id' => $request->user()->id,
            ]);
        }

        $preferences->update($request->validated());

        return response()->json([
            'data' => [
                'email_notifications' => $preferences->email_notifications,
                'sms_notifications' => $preferences->sms_notifications,
                'marketing_communications' => $preferences->marketing_communications,
            ],
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Soft delete
        $user->update(['status' => 'deleted']);
        $user->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
