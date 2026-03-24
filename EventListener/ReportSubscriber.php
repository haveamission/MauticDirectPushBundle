<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_PUSH_NOTIFICATIONS = 'direct_push.notifications';

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuild', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    public function onReportBuild(ReportBuilderEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_PUSH_NOTIFICATIONS])) {
            return;
        }

        $prefix = 'pn.';
        $statPrefix = 'ps.';

        $columns = [
            $prefix.'name' => [
                'label' => 'mautic.direct_push.report.notification_name',
                'type'  => 'string',
            ],
            $prefix.'title' => [
                'label' => 'mautic.direct_push.report.notification_title',
                'type'  => 'string',
            ],
            $prefix.'sent_count' => [
                'label' => 'mautic.direct_push.report.sent_count',
                'type'  => 'int',
            ],
            $prefix.'notification_type' => [
                'label' => 'mautic.direct_push.report.type',
                'type'  => 'string',
            ],
            $prefix.'is_published' => [
                'label' => 'mautic.core.report.is_published',
                'type'  => 'bool',
            ],
            $prefix.'date_added' => [
                'label' => 'mautic.core.report.date_added',
                'type'  => 'datetime',
            ],
            $statPrefix.'date_sent' => [
                'label' => 'mautic.direct_push.report.date_sent',
                'type'  => 'datetime',
            ],
            $statPrefix.'is_failed' => [
                'label' => 'mautic.direct_push.report.is_failed',
                'type'  => 'bool',
            ],
            $statPrefix.'is_clicked' => [
                'label' => 'mautic.direct_push.report.is_clicked',
                'type'  => 'bool',
            ],
            $statPrefix.'date_clicked' => [
                'label' => 'mautic.direct_push.report.date_clicked',
                'type'  => 'datetime',
            ],
        ];

        $event->addTable(
            self::CONTEXT_PUSH_NOTIFICATIONS,
            [
                'display_name' => 'mautic.direct_push.report.push_notifications',
                'columns'      => $columns,
            ]
        );
    }

    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$event->checkContext([self::CONTEXT_PUSH_NOTIFICATIONS])) {
            return;
        }

        $qb = $event->getQueryBuilder();

        $qb->from(MAUTIC_TABLE_PREFIX.'direct_push_notifications', 'pn')
            ->leftJoin('pn', MAUTIC_TABLE_PREFIX.'direct_push_notification_stats', 'ps', 'ps.notification_id = pn.id');

        $event->setQueryBuilder($qb);
    }
}
