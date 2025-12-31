<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate invoice PDF for booking
     */
    public function generateInvoice(Booking $booking): string
    {
        $booking->load(['accommodation.images', 'user', 'payments']);

        $data = $this->prepareInvoiceData($booking);

        $pdf = Pdf::loadView('invoices.booking', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

        $filename = "invoice-{$booking->booking_number}.pdf";
        $path = "invoices/{$filename}";

        // Save to storage
        Storage::disk('public')->put($path, $pdf->output());

        return Storage::url($path);
    }

    /**
     * Stream invoice PDF (download)
     */
    public function downloadInvoice(Booking $booking)
    {
        $booking->load(['accommodation.images', 'user', 'payments']);

        $data = $this->prepareInvoiceData($booking);

        return Pdf::loadView('invoices.booking', $data)
            ->setPaper('a4', 'portrait')
            ->download("invoice-{$booking->booking_number}.pdf");
    }

    /**
     * Preview invoice in browser
     */
    public function previewInvoice(Booking $booking)
    {
        $booking->load(['accommodation.images', 'user', 'payments']);

        $data = $this->prepareInvoiceData($booking);

        return Pdf::loadView('invoices.booking', $data)
            ->setPaper('a4', 'portrait')
            ->stream();
    }

    /**
     * Prepare data for invoice
     */
    private function prepareInvoiceData(Booking $booking): array
    {
        return [
            'booking' => $booking,
            'company' => [
                'name' => config('app.name'),
                'address' => config('invoice.company.address'),
                'phone' => config('invoice.company.phone'),
                'email' => config('invoice.company.email'),
                'website' => config('app.url'),
                'logo' => public_path('images/logo.png'),
            ],
            'invoice' => [
                'number' => $booking->booking_number,
                'date' => $booking->created_at->format('M d, Y'),
                'due_date' => $booking->check_in_date->format('M d, Y'),
            ],
            'customer' => [
                'name' => $booking->guest_first_name.' '.$booking->guest_last_name,
                'email' => $booking->guest_email,
                'phone' => $booking->guest_phone,
            ],
            'items' => $this->getInvoiceItems($booking),
            'totals' => $this->calculateTotals($booking),
            'payment_status' => $booking->payment_status,
            'booking_status' => $booking->status,
        ];
    }

    /**
     * Get invoice line items
     */
    private function getInvoiceItems(Booking $booking): array
    {
        $items = [];

        // Accommodation charges
        $items[] = [
            'description' => $booking->accommodation->name,
            'details' => "{$booking->nights} night(s) - Check-in: {$booking->check_in_date->format('M d, Y')}, Check-out: {$booking->check_out_date->format('M d, Y')}",
            'quantity' => $booking->nights,
            'unit_price' => $booking->subtotal / $booking->nights,
            'amount' => $booking->subtotal,
        ];

        // Cleaning fee
        if ($booking->cleaning_fee > 0) {
            $items[] = [
                'description' => 'Cleaning Fee',
                'details' => 'One-time cleaning service',
                'quantity' => 1,
                'unit_price' => $booking->cleaning_fee,
                'amount' => $booking->cleaning_fee,
            ];
        }

        // Service fee
        if ($booking->service_fee > 0) {
            $items[] = [
                'description' => 'Service Fee',
                'details' => 'Platform service charge',
                'quantity' => 1,
                'unit_price' => $booking->service_fee,
                'amount' => $booking->service_fee,
            ];
        }

        return $items;
    }

    /**
     * Calculate invoice totals
     */
    private function calculateTotals(Booking $booking): array
    {
        return [
            'subtotal' => $booking->subtotal + $booking->cleaning_fee + $booking->service_fee,
            'discount' => $booking->discount,
            'tax' => $booking->tax_amount,
            'total' => $booking->total_amount,
        ];
    }

    /**
     * Generate receipt for payment
     */
    public function generateReceipt(\App\Models\Payment $payment)
    {
        $payment->load(['booking.accommodation', 'booking.user']);

        $data = [
            'payment' => $payment,
            'booking' => $payment->booking,
            'company' => [
                'name' => config('app.name'),
                'address' => config('invoice.company.address'),
                'phone' => config('invoice.company.phone'),
                'email' => config('invoice.company.email'),
            ],
            'receipt' => [
                'number' => 'RCP-'.$payment->id,
                'date' => $payment->created_at->format('M d, Y H:i:s'),
            ],
        ];

        return Pdf::loadView('invoices.receipt', $data)
            ->setPaper('a4', 'portrait')
            ->download("receipt-{$payment->transaction_id}.pdf");
    }
}
