<?php

declare(strict_types=1);

namespace RuMaxBot\Tests\Unit;

use RuMaxBot\DataTransferObjects\WebhookCallbackData;
use RuMaxBot\Services\WebhookSignatureVerifier;
use RuMaxBot\Tests\TestCase;

final class WebhookSignatureVerifierTest extends TestCase
{
    public function test_validates_hmac_signature(): void
    {
        $secret = 'test_webhook_secret';
        $rawBody = '{"event":"message_callback","timestamp":1730000000}';
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        $verifier = new WebhookSignatureVerifier($secret);

        $this->assertTrue($verifier->isValid($rawBody, $signature));
    }

    public function test_rejects_invalid_signature(): void
    {
        $verifier = new WebhookSignatureVerifier('test_webhook_secret');

        $this->assertFalse($verifier->isValid('{"event":"message_callback"}', 'sha256=deadbeef'));
    }

    public function test_rejects_when_secret_empty(): void
    {
        $rawBody = '{"event":"message_callback"}';
        $signature = 'sha256='.hash_hmac('sha256', $rawBody, 'anything');

        $verifier = new WebhookSignatureVerifier('');

        $this->assertFalse($verifier->isValid($rawBody, $signature));
    }

    public function test_parses_webhook_callback_payload(): void
    {
        $data = WebhookCallbackData::fromArray([
            'event' => 'message_callback',
            'timestamp' => 1730000000,
            'channel_id' => 'channel-1',
            'api_key_id' => 'key-1',
            'external_message_id' => '550e8400-e29b-41d4-a716-446655440000',
            'chat_id' => 'chat-1',
            'callback' => [
                'callback_id' => 'cb-1',
                'payload' => 'order:accept',
                'text' => 'Принять',
            ],
            'user' => [
                'user_id' => 123,
                'name' => 'Иван',
                'username' => null,
            ],
            'message' => [
                'mid' => 'mid-1',
                'text' => 'Выберите действие',
            ],
        ]);

        $this->assertSame('message_callback', $data->event);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $data->externalMessageId);
        $this->assertSame('order:accept', $data->callbackPayload());
        $this->assertSame('Принять', $data->callbackText());
    }
}
