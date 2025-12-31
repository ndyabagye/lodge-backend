<?php

use App\Http\Controllers\Api\V1\AccommodationController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\Admin\AccommodationController as AdminAccommodationController;
use App\Http\Controllers\Api\V1\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    });

    // Accommodations (public)
    Route::prefix('accommodations')->group(function () {
        Route::get('/', [AccommodationController::class, 'index']);
        Route::get('/{id}', [AccommodationController::class, 'show']);
        Route::get('/slug/{slug}', [AccommodationController::class, 'showBySlug']);
        Route::get('/{id}/availability', [AccommodationController::class, 'checkAvailability']);
        Route::get('/{id}/reviews', [AccommodationController::class, 'reviews']);
    });

    // Activities (public)
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::get('/{id}', [ActivityController::class, 'show']);
        Route::get('/slug/{slug}', [ActivityController::class, 'showBySlug']);
        Route::get('/{id}/availability', [ActivityController::class, 'checkAvailability']);
        Route::get('/{id}/reviews', [ActivityController::class, 'reviews']);
    });

    // Payment Webhooks (No authentication - validated via signature)
    Route::prefix('webhooks')->group(function () {
        Route::post('/stripe', [App\Http\Controllers\Api\V1\PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');
        Route::post('/flutterwave', [App\Http\Controllers\Api\V1\PaymentController::class, 'flutterwaveWebhook'])->name('webhooks.flutterwave');
        Route::post('/pesapal', [App\Http\Controllers\Api\V1\PaymentController::class, 'pesapalWebhook'])->name('webhooks.pesapal');
        Route::post('/iotec', [App\Http\Controllers\Api\V1\PaymentController::class, 'iotecWebhook'])->name('webhooks.iotec');
    });
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::get('preferences', [AuthController::class, 'getPreferences']);
        Route::put('preferences', [AuthController::class, 'updatePreferences']);
        Route::delete('account', [AuthController::class, 'deleteAccount']);
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::put('/{id}', [BookingController::class, 'update']);
        Route::delete('/{id}', [BookingController::class, 'destroy']);
        Route::post('/check-availability', [BookingController::class, 'checkAvailability']);
        Route::get('/{id}/invoice', [BookingController::class, 'invoice']);
    });

    // Payment routes
    Route::prefix('payments')->controller(App\Http\Controllers\Api\V1\PaymentController::class)->group(function () {
        Route::get('/gateways', 'gateways');
        Route::post('/bookings/{booking}/initialize', 'initialize');
        Route::post('/verify', 'verify');
        Route::post('/{payment}/refund', 'refund')->middleware('staff');
    });

    // admin payment stats
    Route::middleware(['staff'])->group(function () {
        Route::get('admin/payments/statistics', [App\Http\Controllers\Api\V1\PaymentController::class, 'statistics']);
    });

    // Admin routes
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('reports/revenue', [DashboardController::class, 'revenueReport']);
        Route::get('reports/top-accommodations', [DashboardController::class, 'topAccommodations']);

        // Accommodations
        Route::prefix('accommodations')->group(function () {
            Route::get('/', [AdminAccommodationController::class, 'index']);
            Route::post('/', [AdminAccommodationController::class, 'store']);
            Route::put('/{id}', [AdminAccommodationController::class, 'update']);
            Route::delete('/{id}', [AdminAccommodationController::class, 'destroy']);
            Route::post('/{id}/images', [AdminAccommodationController::class, 'uploadImages']);
            Route::delete('/{id}/images/{imageId}', [AdminAccommodationController::class, 'deleteImage']);
        });

        // Activities
        Route::prefix('activities')->group(function () {
            Route::get('/', [AdminActivityController::class, 'index']);
            Route::post('/', [AdminActivityController::class, 'store']);
            Route::put('/{id}', [AdminActivityController::class, 'update']);
            Route::delete('/{id}', [AdminActivityController::class, 'destroy']);
            Route::post('/{id}/images', [AdminActivityController::class, 'uploadImages']);
            Route::delete('/{id}/images/{imageId}', [AdminActivityController::class, 'deleteImage']);
        });

        // Bookings
        Route::prefix('bookings')->group(function () {
            Route::get('/', [AdminBookingController::class, 'index']);
            Route::put('/{id}/status', [AdminBookingController::class, 'updateStatus']);
        });

        // Users
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::put('/{id}/role', [AdminUserController::class, 'updateRole']);
            Route::put('/{id}/suspend', [AdminUserController::class, 'suspend']);
            Route::put('/{id}/activate', [AdminUserController::class, 'activate']);
            Route::delete('/{id}', [AdminUserController::class, 'destroy']);
        });
    });

    // Invoice & Receipt downloads
    Route::get('/bookings/{booking}/invoice', [BookingController::class, 'downloadInvoice']);
    Route::get('/bookings/{booking}/invoice/preview', function (Request $request, Booking $booking) {
        if ($booking->user_id !== $request->user()->id && !$request->user()->isStaff()) {
            abort(403);
        }
        return app(\App\Services\InvoiceService::class)->previewInvoice($booking);
    });

    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'downloadReceipt']);
});
