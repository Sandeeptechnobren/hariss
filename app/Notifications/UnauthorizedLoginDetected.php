<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UnauthorizedLoginDetected extends Notification
{
    protected $log;
    protected $enteredPassword;
    protected $isAdmin;

    public function __construct($log, $enteredPassword = null, $isAdmin = false)
    {
        $this->log = $log;
        $this->enteredPassword = $enteredPassword;
        $this->isAdmin = $isAdmin;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Unauthorized login detected.',
            'email' => $this->log->email,
            'ip_address' => $this->log->ip_address,
            'attempt_count' => $this->log->attempt_count,
            'time' => $this->log->attempt_time,
        ];
    }
    public function toMail($notifiable)
    {
        // 🔐 ADMIN MAIL
        if ($this->isAdmin) {
            return (new MailMessage)
                ->subject('ADMIN ALERT - Unauthorized Login Attempt')
                ->line('⚠ Multiple failed login attempts detected.')
                ->line('User Email: ' . $this->log->email)
                ->line('Username: ' . ($this->log->username ?? 'N/A'))
                ->line('Entered Password: ' . $this->enteredPassword)
                ->line('IP Address: ' . $this->log->ip_address)
                ->line('Attempts: ' . $this->log->attempt_count)
                ->line('Time: ' . $this->log->attempt_time);
        }

        // 🔐 NORMAL USER MAIL
        return (new MailMessage)
            ->subject('Security Alert - Unauthorized Login Attempt')
            ->line('Multiple failed login attempts detected on your account.')
            ->line('IP Address: ' . $this->log->ip_address)
            ->line('Attempts: ' . $this->log->attempt_count)
            ->line('If this was not you, please reset your password immediately.');
    }
}
