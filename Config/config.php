<?php

declare(strict_types=1);

return [
    'name'        => 'Direct Push Notifications',
    'description' => 'Send push notifications directly via FCM and APNs without OneSignal.',
    'version'     => '1.0.0',
    'author'      => 'Lingaist',

    'routes' => [
        'main' => [
            'mautic_direct_push_index' => [
                'path'       => '/push/{page}',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\PushNotificationController::indexAction',
            ],
            'mautic_direct_push_action' => [
                'path'       => '/push/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\PushNotificationController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_direct_push_list' => [
                'path'       => '/push/notifications',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\PushNotificationApiController::getEntitiesAction',
                'method'     => 'GET',
            ],
            'mautic_api_direct_push_get' => [
                'path'       => '/push/notifications/{id}',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\PushNotificationApiController::getEntityAction',
                'method'     => 'GET',
            ],
            'mautic_api_direct_push_create' => [
                'path'       => '/push/notifications/new',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\PushNotificationApiController::newEntityAction',
                'method'     => 'POST',
            ],
            'mautic_api_direct_push_edit' => [
                'path'       => '/push/notifications/{id}/edit',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\PushNotificationApiController::editEntityAction',
                'method'     => 'PUT',
            ],
            'mautic_api_direct_push_delete' => [
                'path'       => '/push/notifications/{id}/delete',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\PushNotificationApiController::deleteEntityAction',
                'method'     => 'DELETE',
            ],
            'mautic_api_direct_push_device_register' => [
                'path'       => '/push/devices',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\DeviceTokenApiController::registerAction',
                'method'     => 'POST',
            ],
            'mautic_api_direct_push_device_remove' => [
                'path'       => '/push/devices/{token}',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\Api\DeviceTokenApiController::removeAction',
                'method'     => 'DELETE',
            ],
        ],
        'public' => [
            'mautic_direct_push_callback' => [
                'path'       => '/push/callback',
                'controller' => 'MauticPlugin\MauticDirectPushBundle\Controller\PublicController::callbackAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.direct_push.menu' => [
                'id'        => 'mautic_direct_push_root',
                'iconClass' => 'ri-notification-3-line',
                'parent'    => 'mautic.core.channels',
                'priority'  => 50,
                'children'  => [
                    'mautic.direct_push.notifications' => [
                        'route' => 'mautic_direct_push_index',
                    ],
                ],
            ],
        ],
    ],

    'parameters' => [
        'direct_push_enabled'              => false,
        'direct_push_fcm_enabled'          => true,
        'direct_push_fcm_service_account'  => null,
        'direct_push_fcm_project_id'       => null,
        'direct_push_apns_enabled'         => true,
        'direct_push_apns_key_contents'    => null,
        'direct_push_apns_key_id'          => null,
        'direct_push_apns_team_id'         => null,
        'direct_push_apns_bundle_id'       => null,
        'direct_push_apns_production'      => false,
        'direct_push_batch_size'           => 500,
    ],

    'services' => [
        'models' => [
            'mautic.model.direct_push.notification' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Model\PushNotificationModel::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'repositories' => [
            'mautic.repository.direct_push.device_token' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Entity\DeviceTokenRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [\MauticPlugin\MauticDirectPushBundle\Entity\DeviceToken::class],
            ],
            'mautic.repository.direct_push.notification' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [\MauticPlugin\MauticDirectPushBundle\Entity\PushNotification::class],
            ],
            'mautic.repository.direct_push.stat' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Entity\StatRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [\MauticPlugin\MauticDirectPushBundle\Entity\Stat::class],
            ],
        ],
        'other' => [
            'mautic.direct_push.transport.fcm' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Transport\FcmTransport::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.direct_push.transport.apns' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Transport\ApnsTransport::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.direct_push.sender' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Service\PushSender::class,
                'arguments' => [
                    'mautic.repository.direct_push.device_token',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                    'mautic.direct_push.transport.fcm',
                    'mautic.direct_push.transport.apns',
                ],
            ],
        ],
        'events' => [
            'mautic.direct_push.subscriber.channel' => [
                'class' => \MauticPlugin\MauticDirectPushBundle\EventListener\ChannelSubscriber::class,
                'tags'  => ['kernel.event_subscriber'],
            ],
            'mautic.direct_push.subscriber.campaign' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.direct_push.sender',
                    'mautic.repository.direct_push.notification',
                ],
                'tags'      => ['kernel.event_subscriber'],
            ],
            'mautic.direct_push.subscriber.broadcast' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\EventListener\BroadcastSubscriber::class,
                'arguments' => [
                    'mautic.repository.direct_push.notification',
                    'mautic.direct_push.sender',
                ],
                'tags'      => ['kernel.event_subscriber'],
            ],
            'mautic.direct_push.subscriber.config' => [
                'class' => \MauticPlugin\MauticDirectPushBundle\EventListener\ConfigSubscriber::class,
                'tags'  => ['kernel.event_subscriber'],
            ],
            'mautic.direct_push.subscriber.timeline' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\EventListener\TimelineSubscriber::class,
                'arguments' => [
                    'mautic.repository.direct_push.stat',
                    'router',
                ],
                'tags'      => ['kernel.event_subscriber'],
            ],
            'mautic.direct_push.subscriber.report' => [
                'class' => \MauticPlugin\MauticDirectPushBundle\EventListener\ReportSubscriber::class,
                'tags'  => ['kernel.event_subscriber'],
            ],
        ],
        'forms' => [
            'mautic.form.type.direct_push.notification' => [
                'class' => \MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationType::class,
            ],
            'mautic.form.type.direct_push.notification_list' => [
                'class'     => \MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationListType::class,
                'arguments' => [
                    'mautic.repository.direct_push.notification',
                ],
            ],
            'mautic.form.type.direct_push.config' => [
                'class' => \MauticPlugin\MauticDirectPushBundle\Form\Type\ConfigType::class,
            ],
        ],
    ],
];
