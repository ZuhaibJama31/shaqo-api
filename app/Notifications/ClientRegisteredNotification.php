<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class ClientRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type'         => 'new_client',
            'title'        => 'New Client Registered',
            'message'      => "{$this->client->name} just created an account",
            'client_id'    => $this->client->id,
            'client_name'  => $this->client->name,
            'client_phone' => $this->client->phone,
            'client_email' => $this->client->email,
        ];
    }
}