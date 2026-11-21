<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

final readonly class WebhookCallbackData
{
    /**
     * @param array{user_id?: int|string, name?: string|null, username?: string|null}|null $user
     * @param array{mid?: string, text?: string}|null $message
     * @param array{callback_id?: string, payload?: string, text?: string}|null $callback
     */
    public function __construct(
        public string $event,
        public int $timestamp,
        public ?string $channelId = null,
        public ?string $apiKeyId = null,
        public ?string $externalMessageId = null,
        public ?string $chatId = null,
        public ?array $callback = null,
        public ?array $user = null,
        public ?array $message = null,
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $callback = isset($payload['callback']) && is_array($payload['callback'])
            ? $payload['callback']
            : null;

        $user = isset($payload['user']) && is_array($payload['user'])
            ? $payload['user']
            : null;

        $message = isset($payload['message']) && is_array($payload['message'])
            ? $payload['message']
            : null;

        return new self(
            event: isset($payload['event']) && is_string($payload['event']) ? $payload['event'] : '',
            timestamp: isset($payload['timestamp']) && is_numeric($payload['timestamp'])
                ? (int) $payload['timestamp']
                : 0,
            channelId: isset($payload['channel_id']) && is_string($payload['channel_id'])
                ? $payload['channel_id']
                : null,
            apiKeyId: isset($payload['api_key_id']) && is_string($payload['api_key_id'])
                ? $payload['api_key_id']
                : null,
            externalMessageId: isset($payload['external_message_id']) && is_string($payload['external_message_id'])
                ? $payload['external_message_id']
                : null,
            chatId: isset($payload['chat_id']) && is_string($payload['chat_id'])
                ? $payload['chat_id']
                : null,
            callback: $callback,
            user: $user,
            message: $message,
        );
    }

    public function callbackPayload(): ?string
    {
        if ($this->callback === null) {
            return null;
        }

        $payload = $this->callback['payload'] ?? null;

        return is_string($payload) ? $payload : null;
    }

    public function callbackText(): ?string
    {
        if ($this->callback === null) {
            return null;
        }

        $text = $this->callback['text'] ?? null;

        return is_string($text) ? $text : null;
    }
}
