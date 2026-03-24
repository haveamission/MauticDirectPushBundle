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
];
