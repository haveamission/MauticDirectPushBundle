<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository;
use MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends FormModel<PushNotification>
 */
class PushNotificationModel extends FormModel
{
    public function getRepository(): PushNotificationRepository
    {
        /** @var PushNotificationRepository $repo */
        $repo = $this->em->getRepository(PushNotification::class);
        return $repo;
    }

    public function getEntity($id = null): ?PushNotification
    {
        if (null === $id) {
            return new PushNotification();
        }

        return $this->getRepository()->find($id);
    }

    /**
     * @param PushNotification $entity
     * @param array<string, mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof PushNotification) {
            throw new MethodNotAllowedHttpException(['PushNotification']);
        }

        if ($action) {
            $options['action'] = $action;
        }

        return $formFactory->create(PushNotificationType::class, $entity, $options);
    }

    public function getPermissionBase(): string
    {
        return 'direct_push:notifications';
    }

    /**
     * @param PushNotification $entity
     * @param bool $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        parent::saveEntity($entity, $unlock);
    }
}
