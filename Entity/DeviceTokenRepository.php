<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<DeviceToken>
 */
class DeviceTokenRepository extends CommonRepository
{
    /**
     * @return DeviceToken[]
     */
    public function getActiveTokensForContact(int $contactId): array
    {
        return $this->createQueryBuilder('dt')
            ->where('IDENTITY(dt.contact) = :contactId')
            ->andWhere('dt.isActive = :active')
            ->setParameter('contactId', $contactId)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findByToken(string $token): ?DeviceToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findByContactAndToken(int $contactId, string $token): ?DeviceToken
    {
        return $this->createQueryBuilder('dt')
            ->where('IDENTITY(dt.contact) = :contactId')
            ->andWhere('dt.token = :token')
            ->setParameter('contactId', $contactId)
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deactivateToken(string $token): void
    {
        $this->createQueryBuilder('dt')
            ->update()
            ->set('dt.isActive', ':inactive')
            ->set('dt.updatedAt', ':now')
            ->where('dt.token = :token')
            ->setParameter('inactive', false)
            ->setParameter('now', new \DateTime())
            ->setParameter('token', $token)
            ->getQuery()
            ->execute();
    }

    public function getTableAlias(): string
    {
        return 'dt';
    }
}
