<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class Stat
{
    private ?int $id = null;
    private ?PushNotification $notification = null;
    private ?Lead $contact = null;
    private ?DeviceToken $deviceToken = null;
    private ?\DateTimeInterface $dateSent = null;
    private bool $isFailed = false;
    private ?string $errorMessage = null;
    private bool $isClicked = false;
    private ?\DateTimeInterface $dateClicked = null;
    private int $retryCount = 0;
    private ?string $source = null;
    private ?int $sourceId = null;
    private ?string $trackingHash = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('direct_push_notification_stats')
            ->setCustomRepositoryClass(StatRepository::class);

        $builder->addBigIntIdField();

        $builder->createManyToOne('notification', PushNotification::class)
            ->addJoinColumn('notification_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('contact', Lead::class)
            ->addJoinColumn('contact_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('deviceToken', DeviceToken::class)
            ->addJoinColumn('device_token_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createField('dateSent', Types::DATETIME_MUTABLE)
            ->columnName('date_sent')
            ->nullable()
            ->build();

        $builder->createField('isFailed', Types::BOOLEAN)
            ->columnName('is_failed')
            ->build();

        $builder->createField('errorMessage', Types::STRING)
            ->columnName('error_message')
            ->length(1024)
            ->nullable()
            ->build();

        $builder->createField('isClicked', Types::BOOLEAN)
            ->columnName('is_clicked')
            ->build();

        $builder->createField('dateClicked', Types::DATETIME_MUTABLE)
            ->columnName('date_clicked')
            ->nullable()
            ->build();

        $builder->createField('retryCount', Types::INTEGER)
            ->columnName('retry_count')
            ->build();

        $builder->createField('source', Types::STRING)
            ->length(191)
            ->nullable()
            ->build();

        $builder->createField('sourceId', Types::INTEGER)
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->createField('trackingHash', Types::STRING)
            ->columnName('tracking_hash')
            ->length(191)
            ->nullable()
            ->build();

        $builder->addIndex(['notification_id', 'date_sent'], 'direct_push_stat_notif_sent_idx');
        $builder->addIndex(['contact_id', 'date_sent'], 'direct_push_stat_contact_sent_idx');
        $builder->addIndex(['tracking_hash'], 'direct_push_stat_hash_idx');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotification(): ?PushNotification
    {
        return $this->notification;
    }

    public function setNotification(?PushNotification $notification): self
    {
        $this->notification = $notification;
        return $this;
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

    public function getDeviceToken(): ?DeviceToken
    {
        return $this->deviceToken;
    }

    public function setDeviceToken(?DeviceToken $deviceToken): self
    {
        $this->deviceToken = $deviceToken;
        return $this;
    }

    public function getDateSent(): ?\DateTimeInterface
    {
        return $this->dateSent;
    }

    public function setDateSent(?\DateTimeInterface $dateSent): self
    {
        $this->dateSent = $dateSent;
        return $this;
    }

    public function isFailed(): bool
    {
        return $this->isFailed;
    }

    public function setIsFailed(bool $isFailed): self
    {
        $this->isFailed = $isFailed;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function isClicked(): bool
    {
        return $this->isClicked;
    }

    public function setIsClicked(bool $isClicked): self
    {
        $this->isClicked = $isClicked;
        return $this;
    }

    public function getDateClicked(): ?\DateTimeInterface
    {
        return $this->dateClicked;
    }

    public function setDateClicked(?\DateTimeInterface $dateClicked): self
    {
        $this->dateClicked = $dateClicked;
        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(?int $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getTrackingHash(): ?string
    {
        return $this->trackingHash;
    }

    public function setTrackingHash(?string $trackingHash): self
    {
        $this->trackingHash = $trackingHash;
        return $this;
    }
}
