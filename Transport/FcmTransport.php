<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Transport;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Log\LoggerInterface;

class FcmTransport implements TransportInterface
{
    private ?ServiceAccountCredentials $credentials = null;

    public function __construct(
        private readonly CoreParametersHelper $parametersHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(string $platform): bool
    {
        if (!$this->parametersHelper->get('direct_push_fcm_enabled')) {
            return false;
        }

        return in_array($platform, ['android', 'web', 'ios'], true);
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): PushResult
    {
        $results = $this->sendBatch([$deviceToken], $title, $body, $data);

        return $results[$deviceToken] ?? new PushResult(false, null, 'No result for token');
    }

    /**
     * Batch send to multiple device tokens over a single multiplexed HTTP/2 connection.
     *
     * @param string[] $deviceTokens
     * @return array<string, PushResult> Keyed by device token
     */
    public function sendBatch(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $results = [];

        $projectId = $this->parametersHelper->get('direct_push_fcm_project_id');
        if (!$projectId) {
            foreach ($deviceTokens as $token) {
                $results[$token] = new PushResult(false, null, 'FCM project ID not configured');
            }
            return $results;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            foreach ($deviceTokens as $token) {
                $results[$token] = new PushResult(false, null, 'Failed to obtain FCM access token');
            }
            return $results;
        }

        $url = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId);
        $headers = [
            'Authorization: Bearer '.$accessToken,
            'Content-Type: application/json',
        ];

        $mh = curl_multi_init();
        if (defined('CURLMOPT_MAX_HOST_CONNECTIONS')) {
            curl_multi_setopt($mh, CURLMOPT_MAX_HOST_CONNECTIONS, 10);
        }

        $handles = [];

        foreach ($deviceTokens as $deviceToken) {
            $message = [
                'message' => [
                    'token'        => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                ],
            ];

            if (!empty($data)) {
                $message['message']['data'] = array_map('strval', $data);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => json_encode($message),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[(int) $ch] = $deviceToken;
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh, 1.0);
            }
        } while ($active && $status === CURLM_OK);

        // Drain completed handles
        while ($info = curl_multi_info_read($mh)) {
            $ch = $info['handle'];
            $deviceToken = $handles[(int) $ch] ?? null;

            if (!$deviceToken) {
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                continue;
            }

            $curlError = curl_error($ch);
            if ($curlError) {
                $this->logger->error('FCM curl error: '.$curlError);
                $results[$deviceToken] = new PushResult(false, null, 'FCM request failed: '.$curlError);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                continue;
            }

            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $results[$deviceToken] = $this->parseResponse($response, $httpCode, $deviceToken);

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);

        foreach ($deviceTokens as $token) {
            if (!isset($results[$token])) {
                $results[$token] = new PushResult(false, null, 'No response received');
            }
        }

        return $results;
    }

    private function parseResponse(string $response, int $httpCode, string $deviceToken): PushResult
    {
        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['name'])) {
            return new PushResult(true, $responseData['name']);
        }

        $errorCode = $responseData['error']['details'][0]['errorCode'] ?? ($responseData['error']['status'] ?? 'UNKNOWN');
        $errorMessage = $responseData['error']['message'] ?? 'Unknown FCM error';
        $tokenInvalid = in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true);

        $this->logger->warning('FCM send failed', [
            'httpCode'   => $httpCode,
            'errorCode'  => $errorCode,
            'error'      => $errorMessage,
            'token'      => substr($deviceToken, 0, 20).'...',
        ]);

        return new PushResult(false, null, $errorMessage, $tokenInvalid);
    }

    private function getAccessToken(): ?string
    {
        try {
            $credentials = $this->getCredentials();
            if (!$credentials) {
                return null;
            }

            $token = $credentials->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('FCM auth token fetch failed: '.$e->getMessage());
            return null;
        }
    }

    private function getCredentials(): ?ServiceAccountCredentials
    {
        if ($this->credentials) {
            return $this->credentials;
        }

        $serviceAccountJson = $this->parametersHelper->get('direct_push_fcm_service_account');
        if (!$serviceAccountJson) {
            $this->logger->error('FCM service account JSON not configured');
            return null;
        }

        $serviceAccount = json_decode($serviceAccountJson, true);
        if (!$serviceAccount) {
            $this->logger->error('Invalid FCM service account JSON');
            return null;
        }

        $this->credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $serviceAccount
        );

        return $this->credentials;
    }
}
