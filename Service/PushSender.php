<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticDirectPushBundle\Entity\DeviceToken;
use MauticPlugin\MauticDirectPushBundle\Entity\DeviceTokenRepository;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use MauticPlugin\MauticDirectPushBundle\Entity\Stat;
use MauticPlugin\MauticDirectPushBundle\Transport\ApnsTransport;
use MauticPlugin\MauticDirectPushBundle\Transport\FcmTransport;
use MauticPlugin\MauticDirectPushBundle\Transport\PushResult;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PushSender
{
    public function __construct(
        private readonly DeviceTokenRepository $deviceTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CoreParametersHelper $parametersHelper,
        private readonly LoggerInterface $logger,
        private readonly FcmTransport $fcmTransport,
        private readonly ApnsTransport $apnsTransport,
    ) {
    }

    /**
     * @return array{sent: int, failed: int, errors: string[]}
     */
    public function sendToContact(
        PushNotification $notification,
        Lead $contact,
        ?string $source = null,
        ?int $sourceId = null,
    ): array {
        $result = ['sent' => 0, 'failed' => 0, 'errors' => []];

        if (!$this->parametersHelper->get('direct_push_enabled')) {
            $result['errors'][] = 'Direct push notifications are disabled';
            return $result;
        }

        $tokens = $this->deviceTokenRepository->getActiveTokensForContact((int) $contact->getId());

        if (empty($tokens)) {
            $result['errors'][] = sprintf('No active device tokens for contact %d', $contact->getId());
            return $result;
        }

        $title = $this->resolveContactTokens($notification->getTitle() ?? '', $contact);
        $body = $this->resolveContactTokens($notification->getBody() ?? '', $contact);

        $data = $notification->getDataJson() ?? [];
        if ($notification->getUrl()) {
            $data['url'] = $notification->getUrl();
        }

        $byPlatform = [];
        foreach ($tokens as $deviceToken) {
            $byPlatform[$deviceToken->getPlatform()][] = $deviceToken;
        }

        $apnsTokens = $byPlatform['ios'] ?? [];
        $fcmTokens = [];
        foreach ($byPlatform as $platform => $platformTokens) {
            if ($platform !== 'ios') {
                $fcmTokens = array_merge($fcmTokens, $platformTokens);
            }
        }

        if (!empty($apnsTokens) && $this->apnsTransport->supports('ios')) {
            $this->sendApnsBatch($apnsTokens, $title, $body, $data, $notification, $contact, $source, $sourceId, $result);
        } elseif (!empty($apnsTokens)) {
            foreach ($apnsTokens as $deviceToken) {
                $result['failed']++;
                $result['errors'][] = 'APNs transport not available for iOS';
            }
        }

        if (!empty($fcmTokens)) {
            $this->sendFcmBatch($fcmTokens, $title, $body, $data, $notification, $contact, $source, $sourceId, $result);
        }

        $this->entityManager->flush();

        return $result;
    }

    /**
     * @param DeviceToken[] $iosTokens
     * @param array{sent: int, failed: int, errors: string[]} $result
     */
    private function sendApnsBatch(
        array $iosTokens,
        string $title,
        string $body,
        array $data,
        PushNotification $notification,
        Lead $contact,
        ?string $source,
        ?int $sourceId,
        array &$result,
    ): void {
        $tokenMap = [];
        foreach ($iosTokens as $deviceToken) {
            $tokenMap[$deviceToken->getToken()] = $deviceToken;
        }

        $batchResults = $this->apnsTransport->sendBatch(
            array_keys($tokenMap),
            $title,
            $body,
            $data
        );

        foreach ($batchResults as $rawToken => $pushResult) {
            $deviceToken = $tokenMap[$rawToken] ?? null;
            if (!$deviceToken) {
                continue;
            }
            $this->recordResult($pushResult, $deviceToken, $notification, $contact, $source, $sourceId, $result);
        }
    }

    /**
     * @param DeviceToken[] $fcmTokens
     * @param array{sent: int, failed: int, errors: string[]} $result
     */
    private function sendFcmBatch(
        array $fcmTokens,
        string $title,
        string $body,
        array $data,
        PushNotification $notification,
        Lead $contact,
        ?string $source,
        ?int $sourceId,
        array &$result,
    ): void {
        $supported = [];
        foreach ($fcmTokens as $deviceToken) {
            if ($this->fcmTransport->supports($deviceToken->getPlatform())) {
                $supported[$deviceToken->getToken()] = $deviceToken;
            } else {
                $result['failed']++;
                $result['errors'][] = sprintf('No transport available for platform: %s', $deviceToken->getPlatform());
            }
        }

        if (empty($supported)) {
            return;
        }

        $batchResults = $this->fcmTransport->sendBatch(
            array_keys($supported),
            $title,
            $body,
            $data
        );

        foreach ($batchResults as $rawToken => $pushResult) {
            $deviceToken = $supported[$rawToken] ?? null;
            if (!$deviceToken) {
                continue;
            }
            $this->recordResult($pushResult, $deviceToken, $notification, $contact, $source, $sourceId, $result);
        }
    }

    /**
     * @param array{sent: int, failed: int, errors: string[]} $result
     */
    private function recordResult(
        PushResult $pushResult,
        DeviceToken $deviceToken,
        PushNotification $notification,
        Lead $contact,
        ?string $source,
        ?int $sourceId,
        array &$result,
    ): void {
        $stat = $this->createStat($notification, $contact, $deviceToken, $pushResult, $source, $sourceId);
        $this->entityManager->persist($stat);

        if ($pushResult->isSuccess()) {
            $result['sent']++;
        } else {
            $result['failed']++;
            $result['errors'][] = $pushResult->getError() ?? 'Unknown error';
        }

        if ($pushResult->isTokenInvalid()) {
            $this->deviceTokenRepository->deactivateToken($deviceToken->getToken());
            $this->logger->info('Deactivated invalid token', [
                'contactId' => $contact->getId(),
                'platform'  => $deviceToken->getPlatform(),
            ]);
        }
    }

    private function createStat(
        PushNotification $notification,
        Lead $contact,
        DeviceToken $deviceToken,
        PushResult $pushResult,
        ?string $source,
        ?int $sourceId,
    ): Stat {
        $trackingHash = bin2hex(random_bytes(16));

        $stat = new Stat();
        $stat->setNotification($notification)
            ->setContact($contact)
            ->setDeviceToken($deviceToken)
            ->setDateSent(new \DateTime())
            ->setIsFailed(!$pushResult->isSuccess())
            ->setErrorMessage($pushResult->getError())
            ->setSource($source)
            ->setSourceId($sourceId)
            ->setTrackingHash($trackingHash);

        return $stat;
    }

    private function resolveContactTokens(string $text, Lead $contact): string
    {
        $fields = $contact->getProfileFields();

        return preg_replace_callback('/\{contactfield=(\w+)\}/', function ($matches) use ($fields) {
            $fieldAlias = $matches[1];
            return $fields[$fieldAlias] ?? '';
        }, $text);
    }
}
