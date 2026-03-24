<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository;
use MauticPlugin\MauticDirectPushBundle\Service\PushSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BroadcastSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PushNotificationRepository $notificationRepository,
        private readonly PushSender $pushSender,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onChannelBroadcast', 0],
        ];
    }

    public function onChannelBroadcast(ChannelBroadcastEvent $event): void
    {
        if (!$event->checkContext('push')) {
            return;
        }

        $id = $event->getId();
        $broadcasts = $this->notificationRepository->getPublishedBroadcasts($id);
        $output = $event->getOutput();

        /** @var PushNotification $notification */
        foreach ($broadcasts as $notification) {
            $sentCount = 0;
            $failedCount = 0;

            $contacts = $event->getContacts('push', $notification->getId());

            foreach ($contacts as $contact) {
                $result = $this->pushSender->sendToContact(
                    $notification,
                    $contact,
                    'broadcast',
                    $notification->getId()
                );

                $sentCount += $result['sent'];
                $failedCount += $result['failed'];
            }

            $notification->incrementSentCount($sentCount);

            $event->setResults(
                sprintf('%s: %d sent, %d failed', $notification->getName(), $sentCount, $failedCount)
            );

            if ($output) {
                $output->writeln(sprintf(
                    '<info>Push "%s": %d sent, %d failed</info>',
                    $notification->getName(),
                    $sentCount,
                    $failedCount
                ));
            }
        }
    }
}
