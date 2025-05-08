<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChangeNotification extends Notification
{
    /**
     * The old email address.
     *
     * @var string
     */
    public $oldEmail;
    
    /**
     * The new email address.
     *
     * @var string
     */
    public $newEmail;

    /**
     * Create a notification instance.
     *
     * @param  string  $oldEmail
     * @param  string  $newEmail
     * @return void
     */
    public function __construct($oldEmail, $newEmail)
    {
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Perubahan Email Akun Karyawan')
            ->view('employees.email-change-notification', [
                'user' => $notifiable,
                'oldEmail' => $this->oldEmail,
                'newEmail' => $this->newEmail
            ]);
    }
}