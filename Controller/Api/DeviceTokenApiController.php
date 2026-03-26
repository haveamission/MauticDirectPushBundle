<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticDirectPushBundle\Entity\DeviceToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class DeviceTokenApiController extends CommonApiController
{
    private EntityManagerInterface $em;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        EntityManagerInterface $em,
    ) {
        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
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

        $contactRepo = $this->em->getRepository(Lead::class);
        $contact = null;

        if ($contactId) {
            $contact = $contactRepo->findOneBy(['id' => (int) $contactId]);
        } elseif ($email) {
            $contact = $contactRepo->findOneBy(['email' => $email]);
        }

        if (null === $contact) {
            $contact = new Lead();
            $contact->setLastActive(new \DateTime());
            if ($email) {
                $contact->setEmail($email);
            }
            $contactRepo->saveEntity($contact);
        }

        $deviceTokenRepo = $this->em->getRepository(DeviceToken::class);
        $deviceToken = $deviceTokenRepo->findByContactAndToken((int) $contact->getId(), $token);

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
        $deviceTokenRepo = $this->em->getRepository(DeviceToken::class);
        $deviceToken = $deviceTokenRepo->findByToken($token);

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
