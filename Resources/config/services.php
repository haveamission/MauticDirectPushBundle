<?php

declare(strict_types=1);

use MauticPlugin\MauticDirectPushBundle\Entity\DeviceTokenRepository;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository;
use MauticPlugin\MauticDirectPushBundle\Entity\StatRepository;
use MauticPlugin\MauticDirectPushBundle\EventListener\BroadcastSubscriber;
use MauticPlugin\MauticDirectPushBundle\EventListener\CampaignSubscriber;
use MauticPlugin\MauticDirectPushBundle\EventListener\ChannelSubscriber;
use MauticPlugin\MauticDirectPushBundle\EventListener\ConfigSubscriber;
use MauticPlugin\MauticDirectPushBundle\EventListener\ReportSubscriber;
use MauticPlugin\MauticDirectPushBundle\EventListener\TimelineSubscriber;
use MauticPlugin\MauticDirectPushBundle\Form\Type\ConfigType;
use MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationListType;
use MauticPlugin\MauticDirectPushBundle\Form\Type\PushNotificationType;
use MauticPlugin\MauticDirectPushBundle\Model\PushNotificationModel;
use MauticPlugin\MauticDirectPushBundle\Service\PushSender;
use MauticPlugin\MauticDirectPushBundle\Transport\ApnsTransport;
use MauticPlugin\MauticDirectPushBundle\Transport\FcmTransport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MauticPlugin\\MauticDirectPushBundle\\', '../')
        ->exclude('../{Config,Entity,Migrations,Resources}');

    $services->set(DeviceTokenRepository::class)
        ->factory([service('doctrine.orm.entity_manager'), 'getRepository'])
        ->args([\MauticPlugin\MauticDirectPushBundle\Entity\DeviceToken::class]);

    $services->set(PushNotificationRepository::class)
        ->factory([service('doctrine.orm.entity_manager'), 'getRepository'])
        ->args([\MauticPlugin\MauticDirectPushBundle\Entity\PushNotification::class]);

    $services->set(StatRepository::class)
        ->factory([service('doctrine.orm.entity_manager'), 'getRepository'])
        ->args([\MauticPlugin\MauticDirectPushBundle\Entity\Stat::class]);

    $services->set(PushNotificationModel::class)
        ->tag('mautic.model', ['alias' => 'direct_push.notification']);

    $services->set(FcmTransport::class);
    $services->set(ApnsTransport::class);
    $services->set(PushSender::class);

    $services->set(ChannelSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(CampaignSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(BroadcastSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(ConfigSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(TimelineSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(ReportSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set(PushNotificationType::class)
        ->tag('form.type');

    $services->set(PushNotificationListType::class)
        ->tag('form.type');

    $services->set(ConfigType::class)
        ->tag('form.type');
};
