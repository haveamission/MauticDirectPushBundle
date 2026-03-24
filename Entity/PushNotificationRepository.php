<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<PushNotification>
 */
class PushNotificationRepository extends CommonRepository
{
    public function getTableAlias(): string
    {
        return 'pn';
    }

    /**
     * @return PushNotification[]
     */
    public function getPublishedBroadcasts(?int $id = null): array
    {
        $qb = $this->createQueryBuilder('pn')
            ->where('pn.isPublished = :published')
            ->andWhere('pn.notificationType = :type')
            ->setParameter('published', true)
            ->setParameter('type', 'list');

        if ($id) {
            $qb->andWhere('pn.id = :id')
                ->setParameter('id', $id);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultOrder(): array
    {
        return [
            ['pn.name', 'ASC'],
        ];
    }
}
