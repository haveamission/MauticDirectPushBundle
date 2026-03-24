<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;

class PushNotification extends FormEntity
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $title = null;
    private ?string $body = null;
    private ?string $url = null;
    private ?string $imageUrl = null;
    private ?string $iconUrl = null;
    private ?array $dataJson = null;
    private string $notificationType = 'template';
    private ?Category $category = null;
    private int $sentCount = 0;
    private ?string $description = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('direct_push_notifications')
            ->setCustomRepositoryClass(PushNotificationRepository::class);

        $builder->addIdColumns();

        $builder->createField('title', Types::STRING)
            ->length(255)
            ->nullable()
            ->build();

        $builder->createField('body', Types::TEXT)
            ->nullable()
            ->build();

        $builder->createField('url', Types::STRING)
            ->length(2048)
            ->nullable()
            ->build();

        $builder->createField('imageUrl', Types::STRING)
            ->columnName('image_url')
            ->length(2048)
            ->nullable()
            ->build();

        $builder->createField('iconUrl', Types::STRING)
            ->columnName('icon_url')
            ->length(2048)
            ->nullable()
            ->build();

        $builder->createField('dataJson', Types::JSON)
            ->columnName('data_json')
            ->nullable()
            ->build();

        $builder->createField('notificationType', Types::STRING)
            ->columnName('notification_type')
            ->length(25)
            ->build();

        $builder->addCategory();

        $builder->createField('sentCount', Types::INTEGER)
            ->columnName('sent_count')
            ->build();

        $builder->addPublishDates();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->isChanged('name', $name);
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->isChanged('description', $description);
        $this->description = $description;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->isChanged('title', $title);
        $this->title = $title;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->isChanged('body', $body);
        $this->body = $body;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->isChanged('url', $url);
        $this->url = $url;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->isChanged('imageUrl', $imageUrl);
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): self
    {
        $this->isChanged('iconUrl', $iconUrl);
        $this->iconUrl = $iconUrl;
        return $this;
    }

    public function getDataJson(): ?array
    {
        return $this->dataJson;
    }

    public function setDataJson(?array $dataJson): self
    {
        $this->isChanged('dataJson', $dataJson);
        $this->dataJson = $dataJson;
        return $this;
    }

    public function getNotificationType(): string
    {
        return $this->notificationType;
    }

    public function setNotificationType(string $notificationType): self
    {
        $this->isChanged('notificationType', $notificationType);
        $this->notificationType = $notificationType;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->isChanged('category', $category);
        $this->category = $category;
        return $this;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function setSentCount(int $sentCount): self
    {
        $this->sentCount = $sentCount;
        return $this;
    }

    public function incrementSentCount(int $count = 1): self
    {
        $this->sentCount += $count;
        return $this;
    }
}
