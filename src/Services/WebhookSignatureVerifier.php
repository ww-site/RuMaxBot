<?php

declare(strict_types=1);

namespace RuMaxBot\Services;

final readonly class WebhookSignatureVerifier
{
    public function __construct(
        private string $secret,
    ) {}

    /**
     * Verify X-MaxBot-Signature: sha256=<hmac-hex> against raw request body.
     */
    public function isValid(string $rawBody, string $signatureHeader): bool
    {
        if ($this->secret === '') {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $this->secret);

        return hash_equals($expected, $signatureHeader);
    }
}
