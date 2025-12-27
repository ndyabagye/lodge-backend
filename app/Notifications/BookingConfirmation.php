<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Booking $booking
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
        return (new MailMessage)
            ->subject('Booking Confirmation - ' . $this->booking->booking_number)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your booking has been confirmed.')
            ->line('Booking Number: ' . $this->booking->booking_number)
            ->line('Accommodation: ' . $this->booking->accommodation->name)
            ->line('Check-in: ' . $this->booking->check_in_date->format('M d, Y'))
            ->line('Check-out: ' . $this->booking->check_out_date->format('M d, Y'))
            ->line('Total Amount: UGX ' . number_format($this->booking->total_amount))
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Thank you for choosing our lodge!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'accommodation_name' => $this->booking->accommodation->name,
            'check_in_date' => $this->booking->check_in_date->format('Y-m-d'),
            'total_amount' => $this->booking->total_amount,
        ];
    }
}
