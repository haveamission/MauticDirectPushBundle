<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Model\PushNotificationModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PushNotificationController extends AbstractStandardFormController
{
    protected function getModelName(): string
    {
        return 'direct_push.notification';
    }

    protected function getJsLoadMethodPrefix(): string
    {
        return 'directPush';
    }

    protected function getRouteBase(): string
    {
        return 'mautic_direct_push';
    }

    protected function getSessionBase($objectId = null): string
    {
        return 'direct_push_notification';
    }

    protected function getTemplateBase(): string
    {
        return '@MauticDirectPush/PushNotification';
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.direct_push';
    }

    protected function getPermissionBase(): string
    {
        return 'direct_push:notifications';
    }

    public function indexAction(Request $request, int $page = 1): Response
    {
        return $this->indexStandard($request, $page);
    }

    public function newAction(Request $request): Response
    {
        return $this->newStandard($request);
    }

    public function editAction(Request $request, int $objectId, bool $ignorePost = false): Response
    {
        return $this->editStandard($request, $objectId, $ignorePost);
    }

    public function viewAction(Request $request, int $objectId): Response
    {
        return $this->viewStandard($request, $objectId);
    }

    public function cloneAction(Request $request, int $objectId): Response
    {
        return $this->cloneStandard($request, $objectId);
    }

    public function deleteAction(Request $request, int $objectId): Response
    {
        return $this->deleteStandard($request, $objectId);
    }

    /**
     * @param PushNotification $entity
     * @return array<string, mixed>
     */
    protected function getViewArguments(array $args, $action): array
    {
        if ('view' === $action) {
            $entity = $args['viewParameters']['item'] ?? null;

            if ($entity instanceof PushNotification) {
                $statRepo = $this->getDoctrine()
                    ->getRepository(\MauticPlugin\MauticDirectPushBundle\Entity\Stat::class);

                $args['viewParameters']['stats'] = $statRepo->getNotificationStats($entity->getId());
            }
        }

        return $args;
    }
}
