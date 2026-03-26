<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticDirectPushBundle\Entity\DeviceToken;
use MauticPlugin\MauticDirectPushBundle\Entity\DeviceTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenApiController extends CommonApiController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function registerAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $token = $data['token'] ?? null;
        $platform = $data['platform'] ?? null;
        $appId = $data['app_id'] ?? '';
        $enabled = $data['enabled'] ?? true;
        $contactId = $data['contact_id'] ?? null;
        $email = $data['email'] ?? null;

        if (!$token || !$platform) {
            return new JsonResponse(
                ['error' => 'token and platform are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!in_array($platform, ['android', 'ios', 'web'], true)) {
            return new JsonResponse(
                ['error' => 'platform must be android, ios, or web'],
                Response::HTTP_BAD_REQUEST
            );
        }

        /** @var DeviceTokenRepository $repo */
        $repo = $this->em->getRepository(DeviceToken::class);

        $contact = null;

        if ($contactId) {
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead');
            $contact = $leadModel->getEntity((int) $contactId);
        } elseif ($email) {
            /** @var LeadModel $leadModel */
            $leadModel = $this->getModel('lead');
            $contact = $leadModel->getRepository()->getContactByEmail($email);
        }

        if (!$contact instanceof Lead) {
            return new JsonResponse(
                ['error' => 'Contact not found. Provide a valid contact_id or email.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $deviceToken = $repo->findByContactAndToken((int) $contact->getId(), $token);

        if (!$deviceToken) {
            $deviceToken = new DeviceToken();
            $deviceToken->setContact($contact)
                ->setToken($token)
                ->setPlatform($platform)
                ->setAppId($appId)
                ->setCreatedAt(new \DateTime());
        }

        $deviceToken->setIsActive((bool) $enabled)
            ->setPlatform($platform)
            ->setAppId($appId)
            ->setUpdatedAt(new \DateTime());

        $this->em->persist($deviceToken);
        $this->em->flush();

        return new JsonResponse([
            'id'         => $deviceToken->getId(),
            'contact_id' => $contact->getId(),
            'token'      => $deviceToken->getToken(),
            'platform'   => $deviceToken->getPlatform(),
            'is_active'  => $deviceToken->isActive(),
        ], Response::HTTP_OK);
    }

    public function removeAction(Request $request, string $token): JsonResponse
    {
        /** @var DeviceTokenRepository $repo */
        $repo = $this->em->getRepository(DeviceToken::class);

        $deviceToken = $repo->findByToken($token);

        if (!$deviceToken) {
            return new JsonResponse(
                ['error' => 'Token not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $deviceToken->setIsActive(false)
            ->setUpdatedAt(new \DateTime());

        $this->em->persist($deviceToken);
        $this->em->flush();

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }
}
