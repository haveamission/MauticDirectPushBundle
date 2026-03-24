<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticDirectPushBundle\Entity\Stat;
use MauticPlugin\MauticDirectPushBundle\Entity\StatRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonController
{
    public function callbackAction(
        Request $request,
        EntityManagerInterface $em,
        RealTimeExecutioner $realTimeExecutioner,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $trackingHash = $data['tracking_hash'] ?? null;

        if (!$trackingHash) {
            return new JsonResponse(['error' => 'tracking_hash is required'], Response::HTTP_BAD_REQUEST);
        }

        /** @var StatRepository $statRepo */
        $statRepo = $em->getRepository(Stat::class);
        $stat = $statRepo->findByTrackingHash($trackingHash);

        if (!$stat) {
            return new JsonResponse(['error' => 'Stat not found'], Response::HTTP_NOT_FOUND);
        }

        if ($stat->isClicked()) {
            return new JsonResponse(['success' => true, 'message' => 'Already recorded']);
        }

        $stat->setIsClicked(true)
            ->setDateClicked(new \DateTime());

        $em->persist($stat);
        $em->flush();

        $contact = $stat->getContact();
        $notification = $stat->getNotification();

        if ($contact && $notification) {
            try {
                $realTimeExecutioner->execute(
                    'direct_push.clicked',
                    [
                        'notification_id' => $notification->getId(),
                        'stat_id'         => $stat->getId(),
                    ],
                    'push',
                    $notification->getId()
                );
            } catch (\Exception $e) {
                // Campaign decision evaluation is non-critical
            }
        }

        return new JsonResponse(['success' => true]);
    }
}
