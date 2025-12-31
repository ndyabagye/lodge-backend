<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $now = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        return [
            'total_bookings' => Booking::count(),
            'total_revenue' => round(
                Booking::where('payment_status', PaymentStatus::PAID)->sum('total_amount'),
                2
            ),
            'pending_bookings' => Booking::where('status', BookingStatus::PENDING)->count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_accommodations' => Accommodation::count(),
            'available_accommodations' => Accommodation::where('status', 'available')->count(),

            // This month stats
            'this_month' => [
                'bookings' => Booking::where('created_at', '>=', $thisMonth)->count(),
                'revenue' => round(
                    Booking::where('payment_status', PaymentStatus::PAID)
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('total_amount'),
                    2
                ),
            ],

            // Last month stats for comparison
            'last_month' => [
                'bookings' => Booking::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count(),
                'revenue' => round(
                    Booking::where('payment_status', PaymentStatus::PAID)
                        ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                        ->sum('total_amount'),
                    2
                ),
            ],

            // Occupancy rate
            'occupancy_rate' => $this->calculateOccupancyRate($thisMonth, $now),

            // Recent activity
            'recent_bookings' => Booking::with(['accommodation', 'user'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($booking) => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'guest_name' => $booking->guest_first_name . ' ' . $booking->guest_last_name,
                    'accommodation' => $booking->accommodation->name,
                    'total_amount' => $booking->total_amount,
                    'status' => $booking->status->label(),
                    'created_at' => $booking->created_at->toISOString(),
                ]),
        ];
    }

    /**
     * Get revenue report
     */
    public function getRevenueReport(Carbon $from, Carbon $to): array
    {
        $bookings = Booking::where('payment_status', PaymentStatus::PAID)
            ->whereBetween('check_in_date', [$from, $to])
            ->select(
                DB::raw('DATE(check_in_date) as date'),
                DB::raw('COUNT(*) as bookings'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $bookings->map(function ($item) {
            return [
                'date' => $item->date,
                'bookings' => (int) $item->bookings,
                'revenue' => round($item->revenue, 2),
            ];
        })->toArray();
    }

    /**
     * Get top performing accommodations
     */
    public function getTopAccommodations(int $limit = 10): array
    {
        return Accommodation::withCount([
                'bookings as bookings_count' => function ($query) {
                    $query->whereNotIn('status', [BookingStatus::CANCELLED]);
                }
            ])
            ->withSum([
                'bookings as total_revenue' => function ($query) {
                    $query->where('payment_status', PaymentStatus::PAID);
                }
            ], 'total_amount')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($accommodation) {
                return [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'type' => $accommodation->type,
                    'bookings' => $accommodation->bookings_count ?? 0,
                    'revenue' => round($accommodation->total_revenue ?? 0, 2),
                    'rating' => (float) $accommodation->rating,
                    'status' => $accommodation->status->label(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate occupancy rate
     */
    private function calculateOccupancyRate(Carbon $from, Carbon $to): float
    {
        $totalDays = $from->diffInDays($to);
        $totalAccommodations = Accommodation::where('status', 'available')->count();
        $totalAvailableDays = $totalDays * $totalAccommodations;

        if ($totalAvailableDays === 0) {
            return 0;
        }

        $bookedNights = Booking::whereBetween('check_in_date', [$from, $to])
            ->whereNotIn('status', [BookingStatus::CANCELLED])
            ->get()
            ->sum(function ($booking) {
                return $booking->nights;
            });

        return round(($bookedNights / $totalAvailableDays) * 100, 2);
    }

    /**
     * Get booking trends (last 7 days)
     */
    public function getBookingTrends(): array
    {
        $days = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $bookings = Booking::whereDate('created_at', $date->toDateString())->count();

            $days->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'bookings' => $bookings,
            ]);
        }

        return $days->toArray();
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(): array
    {
        $total = Booking::count();
        $paid = Booking::where('payment_status', PaymentStatus::PAID)->count();
        $pending = Booking::where('payment_status', PaymentStatus::PENDING)->count();
        $failed = Booking::where('payment_status', PaymentStatus::FAILED)->count();

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'failed' => $failed,
            'paid_percentage' => $total > 0 ? round(($paid / $total) * 100, 2) : 0,
            'pending_percentage' => $total > 0 ? round(($pending / $total) * 100, 2) : 0,
            'failed_percentage' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get accommodation performance summary
     */
    public function getAccommodationPerformance(): array
    {
        $total = Accommodation::count();
        $available = Accommodation::where('status', 'available')->count();
        $maintenance = Accommodation::where('status', 'maintenance')->count();
        $featured = Accommodation::where('featured', true)->count();

        return [
            'total' => $total,
            'available' => $available,
            'maintenance' => $maintenance,
            'featured' => $featured,
            'average_rating' => round(Accommodation::avg('rating'), 2),
            'total_views' => Accommodation::sum('views'),
        ];
    }
}
