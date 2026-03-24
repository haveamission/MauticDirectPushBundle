<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Model\ReportModel;
use MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationListType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 100],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        $event->addChannel(
            'push',
            [
                MessageModel::CHANNEL_FEATURE => [
                    'campaignAction'             => 'direct_push.send',
                    'campaignDecisionsSupported' => [
                        'direct_push.clicked',
                    ],
                    'lookupFormType' => PushNotificationListType::class,
                ],
                LeadModel::CHANNEL_FEATURE  => [],
                ReportModel::CHANNEL_FEATURE => [
                    'table' => 'direct_push_notifications',
                ],
            ]
        );
    }
}
