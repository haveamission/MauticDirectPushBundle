<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Stat>
 */
class StatRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'ps';
    }

    public function findByTrackingHash(string $hash): ?Stat
    {
        return $this->findOneBy(['trackingHash' => $hash]);
    }

    /**
     * @return array{sent: int, failed: int, clicked: int}
     */
    public function getNotificationStats(int $notificationId): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        $qb->select(
            'COUNT(ps.id) as sent',
            'SUM(CASE WHEN ps.is_failed = 1 THEN 1 ELSE 0 END) as failed',
            'SUM(CASE WHEN ps.is_clicked = 1 THEN 1 ELSE 0 END) as clicked'
        )
            ->from(MAUTIC_TABLE_PREFIX.'direct_push_notification_stats', 'ps')
            ->where('ps.notification_id = :notificationId')
            ->setParameter('notificationId', $notificationId);

        $result = $qb->executeQuery()->fetchAssociative();

        return [
            'sent'    => (int) ($result['sent'] ?? 0),
            'failed'  => (int) ($result['failed'] ?? 0),
            'clicked' => (int) ($result['clicked'] ?? 0),
        ];
    }

    /**
     * @return array<array{id: int, notification_id: int, date_sent: string, is_clicked: bool, is_failed: bool}>
     */
    public function getContactStats(int $contactId, int $limit = 20): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        $qb->select('ps.*', 'pn.name as notification_name', 'pn.title as notification_title')
            ->from(MAUTIC_TABLE_PREFIX.'direct_push_notification_stats', 'ps')
            ->leftJoin('ps', MAUTIC_TABLE_PREFIX.'direct_push_notifications', 'pn', 'ps.notification_id = pn.id')
            ->where('ps.contact_id = :contactId')
            ->setParameter('contactId', $contactId)
            ->orderBy('ps.date_sent', 'DESC')
            ->setMaxResults($limit);

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
