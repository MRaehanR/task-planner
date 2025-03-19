<?php

namespace App\Services\Notification;

use Twilio\Rest\Client;

class WhatsAppNotificationService
{
    protected $twilioClient;
    protected $from;

    public function __construct()
    {
        $this->twilioClient = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $this->from = env('TWILIO_WHATSAPP_FROM');
    }

    public function sendWhatsAppMessage($to, $message)
    {
        $this->twilioClient->messages->create(
            "whatsapp:$to",
            [
                'from' => $this->from,
                'body' => $message,
            ]
        );
    }
}
