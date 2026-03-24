<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Transport;

class PushResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $messageId = null,
        private readonly ?string $error = null,
        private readonly bool $tokenInvalid = false,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function isTokenInvalid(): bool
    {
        return $this->tokenInvalid;
    }
}
