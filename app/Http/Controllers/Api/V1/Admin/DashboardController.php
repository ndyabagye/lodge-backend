<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Accommodation;
use App\Services\BookingService;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private DashboardService $dashboardService,
        private BookingService $bookingService
    ) {}


    /**
     * Get dashboard statistics
     */
    public function index(): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'total_revenue' => Booking::where('payment_status', 'paid')->sum('total_amount'),
            'monthly_revenue' => Booking::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisMonth)
                ->sum('total_amount'),
            'active_users' => User::where('status', 'active')->count(),
            'total_accommodations' => Accommodation::count(),
            'available_accommodations' => Accommodation::where('status', 'available')->count(),
            'occupied_today' => Booking::where('check_in_date', '<=', $today)
                ->where('check_out_date', '>', $today)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->count(),
        ];

        // Calculate occupancy rate
        $totalRooms = Accommodation::where('status', 'available')->count();
        $stats['occupancy_rate'] = $totalRooms > 0
            ? round(($stats['occupied_today'] / $totalRooms) * 100, 2)
            : 0;

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get revenue report
     */
    public function revenueReport(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->from ? Carbon::parse($request->from) : Carbon::now()->subDays(30);
        $to = $request->to ? Carbon::parse($request->to) : Carbon::now();

        $revenue = Booking::where('payment_status', 'paid')
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as bookings'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $revenue,
            'meta' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'total_revenue' => $revenue->sum('revenue'),
                'total_bookings' => $revenue->sum('bookings'),
            ],
        ]);
    }

    /**
     * Get top performing accommodations
     */
    public function topAccommodations(): JsonResponse
    {
        $accommodations = Accommodation::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['confirmed', 'checked_in', 'checked_out']);
        }])
        ->with('images')
        ->orderBy('bookings_count', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'data' => $accommodations->map(function ($accommodation) {
                return [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'bookings' => $accommodation->bookings_count,
                    'rating' => (float) $accommodation->rating,
                    'revenue' => Booking::where('accommodation_id', $accommodation->id)
                        ->where('payment_status', 'paid')
                        ->sum('total_amount'),
                    'featured_image' => $accommodation->images->where('is_featured', true)->first()?->url,
                ];
            }),
        ]);
    }
}
