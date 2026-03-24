<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Transport;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Pushok\AuthProvider;
use Pushok\Client;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;
use Psr\Log\LoggerInterface;

class ApnsTransport implements TransportInterface
{
    private ?AuthProvider\Token $authProvider = null;

    public function __construct(
        private readonly CoreParametersHelper $parametersHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $platform): bool
    {
        if (!$this->parametersHelper->get('direct_push_apns_enabled')) {
            return false;
        }

        return $platform === 'ios';
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): PushResult
    {
        $authProvider = $this->getAuthProvider();
        if (!$authProvider) {
            return new PushResult(false, null, 'APNs credentials not configured');
        }

        $isProduction = (bool) $this->parametersHelper->get('direct_push_apns_production');

        try {
            $alert = Alert::create()
                ->setTitle($title)
                ->setBody($body);

            $payload = Payload::create()
                ->setAlert($alert)
                ->setSound('default');

            foreach ($data as $key => $value) {
                $payload->setCustomValue($key, $value);
            }

            $notification = new Notification($payload, $deviceToken);

            $client = new Client($authProvider, $isProduction);
            $client->addNotifications([$notification]);

            $responses = $client->push();

            if (empty($responses)) {
                return new PushResult(false, null, 'No response from APNs');
            }

            $response = $responses[0];
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return new PushResult(true, $response->getApnsId());
            }

            $errorReason = $response->getErrorReason() ?? 'Unknown';
            $tokenInvalid = in_array($errorReason, ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic'], true);

            $this->logger->warning('APNs send failed', [
                'statusCode'  => $statusCode,
                'errorReason' => $errorReason,
                'description' => $response->getErrorDescription(),
                'token'       => substr($deviceToken, 0, 20).'...',
            ]);

            return new PushResult(false, null, $errorReason, $tokenInvalid);
        } catch (\Exception $e) {
            $this->logger->error('APNs exception: '.$e->getMessage());
            return new PushResult(false, null, 'APNs error: '.$e->getMessage());
        }
    }

    /**
     * Batch send to multiple device tokens. Leverages pushok's
     * multiplexed HTTP/2 connections for concurrent delivery.
     *
     * @param string[] $deviceTokens
     * @return array<string, PushResult> Keyed by device token
     */
    public function sendBatch(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $results = [];

        $authProvider = $this->getAuthProvider();
        if (!$authProvider) {
            foreach ($deviceTokens as $token) {
                $results[$token] = new PushResult(false, null, 'APNs credentials not configured');
            }
            return $results;
        }

        $isProduction = (bool) $this->parametersHelper->get('direct_push_apns_production');

        try {
            $alert = Alert::create()
                ->setTitle($title)
                ->setBody($body);

            $payload = Payload::create()
                ->setAlert($alert)
                ->setSound('default');

            foreach ($data as $key => $value) {
                $payload->setCustomValue($key, $value);
            }

            $notifications = [];
            foreach ($deviceTokens as $token) {
                $notifications[] = new Notification($payload, $token);
            }

            $client = new Client($authProvider, $isProduction);
            $client->addNotifications($notifications);

            $responses = $client->push();

            foreach ($responses as $response) {
                $token = $response->getDeviceToken();
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    $results[$token] = new PushResult(true, $response->getApnsId());
                } else {
                    $errorReason = $response->getErrorReason() ?? 'Unknown';
                    $tokenInvalid = in_array($errorReason, ['BadDeviceToken', 'Unregistered', 'DeviceTokenNotForTopic'], true);
                    $results[$token] = new PushResult(false, null, $errorReason, $tokenInvalid);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('APNs batch exception: '.$e->getMessage());
            foreach ($deviceTokens as $token) {
                if (!isset($results[$token])) {
                    $results[$token] = new PushResult(false, null, 'APNs error: '.$e->getMessage());
                }
            }
        }

        return $results;
    }

    private function getAuthProvider(): ?AuthProvider\Token
    {
        if ($this->authProvider) {
            return $this->authProvider;
        }

        $keyContents = $this->parametersHelper->get('direct_push_apns_key_contents');
        $keyId = $this->parametersHelper->get('direct_push_apns_key_id');
        $teamId = $this->parametersHelper->get('direct_push_apns_team_id');
        $bundleId = $this->parametersHelper->get('direct_push_apns_bundle_id');

        if (!$keyContents || !$keyId || !$teamId || !$bundleId) {
            $this->logger->error('APNs credentials not fully configured');
            return null;
        }

        try {
            $this->authProvider = AuthProvider\Token::create([
                'key_id'              => $keyId,
                'team_id'             => $teamId,
                'app_bundle_id'       => $bundleId,
                'private_key_content' => $keyContents,
                'private_key_secret'  => null,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create APNs auth provider: '.$e->getMessage());
            return null;
        }

        return $this->authProvider;
    }
}
