<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->payment->booking;

        return (new MailMessage)
            ->subject('Payment Received - ' . $booking->booking_number)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('We have received your payment.')
            ->line('Booking Number: ' . $booking->booking_number)
            ->line('Amount: ' . $this->payment->currency . ' ' . number_format($this->payment->amount))
            ->line('Payment Method: ' . ucfirst($this->payment->payment_method))
            ->line('Transaction ID: ' . $this->payment->transaction_id)
            ->action('View Booking', url('/bookings/' . $booking->id))
            ->line('Thank you for your payment!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'booking_number' => $this->payment->booking->booking_number,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'transaction_id' => $this->payment->transaction_id,
        ];
    }
}
