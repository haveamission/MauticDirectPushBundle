<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Transport;

interface TransportInterface
{
    public function send(string $deviceToken, string $title, string $body, array $data = []): PushResult;

    public function supports(string $platform): bool;
}
