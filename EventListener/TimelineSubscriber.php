<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticDirectPushBundle\Entity\StatRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class TimelineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StatRepository $statRepository,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    public function onTimelineGenerate(LeadTimelineEvent $event): void
    {
        $this->addSentEvents($event);
        $this->addClickedEvents($event);
    }

    private function addSentEvents(LeadTimelineEvent $event): void
    {
        $eventTypeKey = 'direct_push.sent';
        $eventTypeName = 'Push Notification Sent';

        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('pushNotificationStatDetails');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $contactId = (int) $event->getLead()->getId();
        $stats = $this->statRepository->getContactStats($contactId);

        foreach ($stats as $stat) {
            if (empty($stat['date_sent'])) {
                continue;
            }

            $eventData = [
                'event'      => $eventTypeKey,
                'eventId'    => $eventTypeKey.$stat['id'],
                'eventType'  => $eventTypeName,
                'eventLabel' => $stat['notification_name'] ?? 'Push Notification',
                'timestamp'  => $stat['date_sent'],
                'icon'       => 'ri-notification-3-line',
                'extra'      => [
                    'stat' => $stat,
                    'type' => 'sent',
                ],
                'contentTemplate' => '@MauticDirectPush/SubscribedEvents/Timeline/index.html.twig',
            ];

            if (!empty($stat['notification_id'])) {
                $eventData['eventLabel'] = [
                    'label' => $stat['notification_name'] ?? 'Push Notification',
                    'href'  => $this->router->generate('mautic_direct_push_action', [
                        'objectAction' => 'view',
                        'objectId'     => $stat['notification_id'],
                    ]),
                ];
            }

            $event->addEvent($eventData);
        }
    }

    private function addClickedEvents(LeadTimelineEvent $event): void
    {
        $eventTypeKey = 'direct_push.clicked';
        $eventTypeName = 'Push Notification Clicked';

        $event->addEventType($eventTypeKey, $eventTypeName);

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        $contactId = (int) $event->getLead()->getId();
        $stats = $this->statRepository->getContactStats($contactId);

        foreach ($stats as $stat) {
            if (empty($stat['date_clicked']) || !$stat['is_clicked']) {
                continue;
            }

            $event->addEvent([
                'event'      => $eventTypeKey,
                'eventId'    => $eventTypeKey.$stat['id'],
                'eventType'  => $eventTypeName,
                'eventLabel' => $stat['notification_name'] ?? 'Push Notification',
                'timestamp'  => $stat['date_clicked'],
                'icon'       => 'ri-cursor-line',
                'extra'      => [
                    'stat' => $stat,
                    'type' => 'clicked',
                ],
                'contentTemplate' => '@MauticDirectPush/SubscribedEvents/Timeline/index.html.twig',
            ]);
        }
    }
}
