<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

class FirstAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public array $_data = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(array $content)
    {
        $this->_data['first_name'] = $content['first_name'];
        $this->_data['url'] = config('app.url');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return MailMessage
     */
    public function toMail()
    {
        return (new MailMessage)
            ->subject(trans('mail.welcome', ['name' => $this->_data['first_name']]))
            ->markdown('notifications.FirstAdmin', [
                'first_name' => $this->_data['first_name'],
                'url' => config('app.url'),
            ])
            ->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()->addTextHeader(
                    'X-System-Sender', 'Snipe-IT'
                );
            });
    }
}
