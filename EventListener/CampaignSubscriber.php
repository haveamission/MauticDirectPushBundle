<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\DecisionEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use MauticPlugin\MauticDirectPushBundle\DirectPushEvents;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository;
use MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationListType;
use MauticPlugin\MauticDirectPushBundle\Service\PushSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PushSender $pushSender,
        private readonly PushNotificationRepository $notificationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD          => ['onCampaignBuild', 0],
            DirectPushEvents::EXECUTE_CAMPAIGN_ACTION   => ['onCampaignAction', 0],
            DirectPushEvents::ON_CAMPAIGN_TRIGGER_DECISION => ['onCampaignDecision', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $event->addAction(
            'direct_push.send',
            [
                'label'          => 'mautic.direct_push.campaign.action.send',
                'description'    => 'mautic.direct_push.campaign.action.send.descr',
                'batchEventName' => DirectPushEvents::EXECUTE_CAMPAIGN_ACTION,
                'formType'       => PushNotificationListType::class,
                'channel'        => 'push',
                'channelIdField' => 'notification',
            ]
        );

        $event->addDecision(
            'direct_push.clicked',
            [
                'label'          => 'mautic.direct_push.campaign.decision.clicked',
                'description'    => 'mautic.direct_push.campaign.decision.clicked.descr',
                'eventName'      => DirectPushEvents::ON_CAMPAIGN_TRIGGER_DECISION,
                'channel'        => 'push',
                'channelIdField' => 'notification',
            ]
        );
    }

    public function onCampaignAction(PendingEvent $event): void
    {
        $config = $event->getEvent()->getProperties();
        $notificationId = (int) ($config['notification'] ?? 0);

        if (!$notificationId) {
            $event->failAll('No notification selected');
            return;
        }

        $notification = $this->notificationRepository->find($notificationId);
        if (!$notification instanceof PushNotification) {
            $event->failAll('Notification not found: '.$notificationId);
            return;
        }

        $pending = $event->getPending();

        foreach ($pending as $logEntry) {
            $contact = $logEntry->getLead();

            $result = $this->pushSender->sendToContact(
                $notification,
                $contact,
                'campaign.event',
                $event->getEvent()->getId()
            );

            if ($result['sent'] > 0) {
                $event->pass($logEntry);
            } else {
                $event->fail(
                    $logEntry,
                    implode('; ', $result['errors'])
                );
            }
        }

        $notification->incrementSentCount(count($pending));
    }

    public function onCampaignDecision(DecisionEvent $event): void
    {
        $config = $event->getLog()->getEvent()->getProperties();
        $notificationId = (int) ($config['notification'] ?? 0);
        $passedNotificationId = (int) ($event->getPassthrough()['notification_id'] ?? 0);

        if ($notificationId && $passedNotificationId && $notificationId === $passedNotificationId) {
            $event->setAsApplicable();
        }
    }
}
