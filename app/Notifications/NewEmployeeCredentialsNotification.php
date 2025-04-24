<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class NewEmployeeCredentialsNotification extends Notification
{
    /**
     * The generated password.
     *
     * @var string
     */
    public $password;

    /**
     * Create a notification instance.
     *
     * @param  string  $password
     * @return void
     */
    public function __construct($password)
    {
        $this->password = $password;
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
            ->subject('Informasi Akun Karyawan Baru')
            ->view('employees.new-employee-credentials', [
                'user' => $notifiable,
                'password' => $this->password
            ]);
    }
}