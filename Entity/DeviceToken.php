<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class DeviceToken
{
    private ?int $id = null;
    private ?Lead $contact = null;
    private string $token = '';
    private string $platform = '';
    private bool $isActive = true;
    private string $appId = '';
    private ?\DateTimeInterface $createdAt = null;
    private ?\DateTimeInterface $updatedAt = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('direct_push_device_tokens')
            ->setCustomRepositoryClass(DeviceTokenRepository::class);

        $builder->addId();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('token', Types::STRING)
            ->length(512)
            ->build();

        $builder->createField('platform', Types::STRING)
            ->length(20)
            ->build();

        $builder->createField('isActive', Types::BOOLEAN)
            ->columnName('is_active')
            ->build();

        $builder->createField('appId', Types::STRING)
            ->columnName('app_id')
            ->length(255)
            ->build();

        $builder->createField('createdAt', Types::DATETIME_MUTABLE)
            ->columnName('created_at')
            ->build();

        $builder->createField('updatedAt', Types::DATETIME_MUTABLE)
            ->columnName('updated_at')
            ->build();

        $builder->addUniqueConstraint(['contact_id', 'token'], 'direct_push_contact_token_uniq');
        $builder->addIndex(['contact_id', 'is_active'], 'direct_push_contact_active_idx');
        $builder->addIndex(['platform', 'is_active'], 'direct_push_platform_active_idx');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?Lead
    {
        return $this->contact;
    }

    public function setContact(?Lead $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
