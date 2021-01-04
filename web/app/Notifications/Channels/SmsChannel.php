<?php

namespace App\Notifications\Channels;

use Overtrue\EasySms\EasySms;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    /**
     * The SMS notification driver.
     *
     * @var \Overtrue\EasySms\EasySms
     */
    protected $sms;

    /**
     * Create the SMS notification channel instance.
     *
     * @param \Overtrue\EasySms\EasySms $sms
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function __construct(EasySms $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Nexmo\Message\Message
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('sms')) {
            return;
        }

        $message = $notification->toSms($notifiable, $this->sms->getConfig());

        return $this->sms->send($to, $message);
    }
}
