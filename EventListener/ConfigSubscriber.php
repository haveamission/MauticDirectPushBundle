<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'formAlias'  => 'direct_push_config',
            'formTheme'  => '@MauticDirectPush/FormTheme/Config/_config_direct_push_config_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticDirectPushBundle'),
        ]);
    }

    public function onConfigSave(ConfigEvent $event): void
    {
        $data = $event->getConfig('direct_push_config');

        if (isset($data['direct_push_fcm_service_account']) && !empty($data['direct_push_fcm_service_account'])) {
            $json = json_decode($data['direct_push_fcm_service_account'], true);
            if ($json && isset($json['project_id'])) {
                $data['direct_push_fcm_project_id'] = $json['project_id'];
            }
        }

        $event->setConfig($data, 'direct_push_config');
    }
}
