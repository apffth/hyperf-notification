<?php

declare(strict_types=1);

namespace Apffth\Hyperf\Notification;

use Apffth\Hyperf\Notification\Channels\ChannelInterface;
use Apffth\Hyperf\Notification\Contracts\EventDispatcherInterface;
use Apffth\Hyperf\Notification\Events\NotificationFailed;
use Apffth\Hyperf\Notification\Events\NotificationSending;
use Apffth\Hyperf\Notification\Events\NotificationSent;
use Apffth\Hyperf\Notification\Exceptions\NotificationException;
use Throwable;

use function Hyperf\AsyncQueue\dispatch;
use function Hyperf\Collection\collect;

class NotificationSender
{
    /**
     * The channel manager instance.
     */
    protected ChannelManager $channelManager;

    /**
     * The event dispatcher instance.
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Create a new notification sender instance.
     */
    public function __construct(ChannelManager $channelManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->channelManager  = $channelManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param mixed $notifiable
     */
    public function send($notifiable, Notification $notification): void
    {
        if ($notification->shouldQueue($notifiable)) {
            $this->queueNotification($notifiable, $notification);
            return;
        }

        if (! $notification->shouldSend($notifiable)) {
            return;
        }

        $this->sendNow($notifiable, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param mixed $notifiable
     */
    public function sendNow($notifiable, Notification $notification): void
    {
        $notification->setId();

        $channels = collect($notification->via($notifiable))
            ->filter(fn ($channel) => ! in_array($channel, $notification->sentChannels))
            ->all();

        if (empty($channels)) {
            return;
        }

        $responses = [];
        $failed    = null;

        foreach ($channels as $channel) {
            $sendingEvent = new NotificationSending($notifiable, $notification, $channel);
            $this->eventDispatcher->dispatchSending($sendingEvent);

            if (! $sendingEvent->shouldSend()) {
                continue;
            }

            try {
                $channelInstance = $this->channelManager->get($channel);

                if (! $channelInstance instanceof ChannelInterface) {
                    throw new NotificationException(500, 'Channel instance not found');
                }

                $response = $channelInstance->send($notifiable, $notification);

                $notification->addSentChannel($channel);

                $responses[$channel] = $response;

                $sentEvent = new NotificationSent($notifiable, $notification, $channel, $response);
                $this->eventDispatcher->dispatchSent($sentEvent);
            } catch (Throwable $e) {
                if (is_null($failed)) {
                    $failed = $e;
                }
                $failedEvent = new NotificationFailed($notifiable, $notification, $channel, $e);
                $this->eventDispatcher->dispatchFailed($failedEvent);
                $notification->failed($e);
            }
        }

        if (! empty($responses)) {
            $notification->setChannelResponses(array_merge($notification->getChannelResponses(), $responses));
        }

        if (method_exists($notification, 'afterChannelsSend')) {
            $notification->afterChannelsSend($notifiable);
        }

        if ($failed) {
            throw $failed;
        }
    }



    /**
     * Queue the given notification.
     *
     * @param mixed $notifiable
     */
    protected function queueNotification($notifiable, Notification $notification): void
    {
        $job = new NotificationJob($notifiable, $notification);

        dispatch(
            $job,
            $notification->getDelay()     ?? 0,
            $notification->getTries()     ?? 1,
            $notification->getQueueName() ?? 'notification'
        );
    }
}
