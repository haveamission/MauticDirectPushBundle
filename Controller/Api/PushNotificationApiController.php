<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Model\PushNotificationModel;
use Symfony\Component\HttpFoundation\Response;

class PushNotificationApiController extends CommonApiController
{
    /**
     * @var PushNotificationModel|null
     */
    protected $model;

    public function initialize(): void
    {
        $this->model            = $this->getModel('direct_push.notification');
        $this->entityClass      = PushNotification::class;
        $this->entityNameOne    = 'notification';
        $this->entityNameMulti  = 'notifications';
        $this->permissionBase   = 'direct_push:notifications';
        $this->serializerGroups = ['pushNotificationDetails', 'categoryList', 'publishDetails'];

        parent::initialize();
    }
}
