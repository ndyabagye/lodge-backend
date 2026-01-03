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
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES (No Authentication Required)
// ============================================
Route::prefix('v1')->group(function () {

    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    });

    // Accommodations (Public)
    Route::prefix('accommodations')->group(function () {
        Route::get('/', [AccommodationController::class, 'index']);
        Route::get('/{accommodation}', [AccommodationController::class, 'show']);
        Route::get('/slug/{slug}', [AccommodationController::class, 'showBySlug']);
        Route::get('/{accommodation}/availability', [AccommodationController::class, 'checkAvailability']);
        Route::get('/{accommodation}/reviews', [AccommodationController::class, 'reviews']);
    });

    // Activities (Public)
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::get('/{activity}', [ActivityController::class, 'show']);
        Route::get('/slug/{slug}', [ActivityController::class, 'showBySlug']);
        Route::get('/{activity}/availability', [ActivityController::class, 'checkAvailability']);
        Route::get('/{activity}/reviews', [ActivityController::class, 'reviews']);
    });

    // Guest Booking Routes (No Auth Required - Uses booking_number + email verification)
    Route::prefix('bookings')->group(function () {
        // Check availability - Anyone can check
        Route::post('/check-availability', [BookingController::class, 'checkAvailability']);

        // Create booking - Works for both guest and authenticated users
        Route::post('/', [BookingController::class, 'store']);

        // Guest booking tracking (requires ?email=xxx query parameter)
        Route::get('/guest/{bookingNumber}', [BookingController::class, 'showGuestBooking'])
            ->where('bookingNumber', '[A-Z0-9\-]+');

        // Guest invoice routes (requires ?email=xxx query parameter)
        Route::get('/guest/{bookingNumber}/invoice/preview', [BookingController::class, 'previewGuestInvoice'])
            ->where('bookingNumber', '[A-Z0-9\-]+');

        Route::get('/guest/{bookingNumber}/invoice', [BookingController::class, 'downloadGuestInvoice'])
            ->where('bookingNumber', '[A-Z0-9\-]+');
    });

    // Guest Payment Routes
    Route::prefix('payments')->group(function () {
        // Get available payment gateways
        Route::get('/gateways', [PaymentController::class, 'gateways']);

        // Initialize payment for guest booking (requires ?email=xxx query parameter)
        Route::post('/guest/{bookingNumber}/initialize', [PaymentController::class, 'initializeGuest'])
            ->where('bookingNumber', '[A-Z0-9\-]+');

        // Verify payment (public - called by payment gateway callback)
        Route::post('/verify', [PaymentController::class, 'verify']);

        // Guest receipt download (requires ?email=xxx query parameter)
        Route::get('/guest/{bookingNumber}/receipt', [PaymentController::class, 'downloadGuestReceipt'])
            ->where('bookingNumber', '[A-Z0-9\-]+');
    });

    // Payment Webhooks (No authentication - validated via signature)
    Route::prefix('webhooks')->group(function () {
        Route::post('/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');
        Route::post('/flutterwave', [PaymentController::class, 'flutterwaveWebhook'])->name('webhooks.flutterwave');
        Route::post('/pesapal', [PaymentController::class, 'pesapalWebhook'])->name('webhooks.pesapal');
        Route::post('/iotec', [PaymentController::class, 'iotecWebhook'])->name('webhooks.iotec');
    });
});

// ============================================
// PROTECTED ROUTES (Authentication Required)
// ============================================
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    // Auth Routes (Authenticated)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // User Profile Routes
    Route::prefix('users')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::get('preferences', [AuthController::class, 'getPreferences']);
        Route::put('preferences', [AuthController::class, 'updatePreferences']);
        Route::delete('account', [AuthController::class, 'deleteAccount']);
    });

    // Authenticated User Bookings
    Route::prefix('bookings')->group(function () {
        // List MY bookings
        Route::get('/', [BookingController::class, 'index']);

        // Get single booking by ID
        Route::get('/{booking}', [BookingController::class, 'show']);

        // Update MY booking
        Route::put('/{booking}', [BookingController::class, 'update']);

        // Cancel MY booking
        Route::delete('/{booking}', [BookingController::class, 'destroy']);

        // MY booking invoice routes
        Route::get('/{booking}/invoice/preview', [BookingController::class, 'previewInvoice']);
        Route::get('/{booking}/invoice', [BookingController::class, 'downloadInvoice']);
    });

    // Authenticated Payment Routes
    Route::prefix('payments')->group(function () {
        // Initialize payment for MY booking (by booking ID, not booking number)
        Route::post('/bookings/{booking}/initialize', [PaymentController::class, 'initialize']);

        // Download MY receipt
        Route::get('/{payment}/receipt', [PaymentController::class, 'downloadReceipt']);

        // Request refund (Staff only)
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])->middleware('staff');
    });

    // Payment Statistics (Staff only)
    Route::middleware(['staff'])->group(function () {
        Route::get('admin/payments/statistics', [PaymentController::class, 'statistics']);
    });

    // ============================================
    // ADMIN ROUTES (Admin Role Required)
    // ============================================
    Route::prefix('admin')->middleware(['admin'])->group(function () {

        // Dashboard & Reports
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('reports/revenue', [DashboardController::class, 'revenueReport']);
        Route::get('reports/top-accommodations', [DashboardController::class, 'topAccommodations']);

        // Accommodation Management
        Route::prefix('accommodations')->group(function () {
            Route::get('/', [AdminAccommodationController::class, 'index']);
            Route::post('/', [AdminAccommodationController::class, 'store']);
            Route::put('/{accommodation}', [AdminAccommodationController::class, 'update']);
            Route::delete('/{accommodation}', [AdminAccommodationController::class, 'destroy']);
            Route::post('/{accommodation}/images', [AdminAccommodationController::class, 'uploadImages']);
            Route::delete('/{accommodation}/images/{image}', [AdminAccommodationController::class, 'deleteImage']);
        });

        // Activity Management
        Route::prefix('activities')->group(function () {
            Route::get('/', [AdminActivityController::class, 'index']);
            Route::post('/', [AdminActivityController::class, 'store']);
            Route::put('/{activity}', [AdminActivityController::class, 'update']);
            Route::delete('/{activity}', [AdminActivityController::class, 'destroy']);
            Route::post('/{activity}/images', [AdminActivityController::class, 'uploadImages']);
            Route::delete('/{activity}/images/{image}', [AdminActivityController::class, 'deleteImage']);
        });

        // Booking Management
        Route::prefix('bookings')->group(function () {
            Route::get('/', [AdminBookingController::class, 'index']);
            Route::put('/{booking}/status', [AdminBookingController::class, 'updateStatus']);
        });

        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::put('/{user}/role', [AdminUserController::class, 'updateRole']);
            Route::put('/{user}/suspend', [AdminUserController::class, 'suspend']);
            Route::put('/{user}/activate', [AdminUserController::class, 'activate']);
            Route::delete('/{user}', [AdminUserController::class, 'destroy']);
        });
    });
});
