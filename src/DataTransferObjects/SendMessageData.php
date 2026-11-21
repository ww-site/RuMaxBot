<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

use RuMaxBot\Enums\MessageFormat;

final readonly class SendMessageData
{
    /**
     * @param list<InlineKeyboardAttachment>|null $attachments
     */
    public function __construct(
        public string $text,
        public MessageFormat $format = MessageFormat::Markdown,
        public ?string $externalMessageId = null,
        public ?array $attachments = null,
    ) {}

    /**
     * @return array{
     *     text: string,
     *     format: string,
     *     external_message_id?: string,
     *     attachments?: list<array{type: string, payload: array{buttons: list<list<array<string, string>>>}}>
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'text' => $this->text,
            'format' => $this->format->value,
        ];

        if ($this->externalMessageId !== null && $this->externalMessageId !== '') {
            $payload['external_message_id'] = $this->externalMessageId;
        }

        if ($this->attachments !== null && $this->attachments !== []) {
            $payload['attachments'] = array_map(
                static fn (InlineKeyboardAttachment $attachment): array => $attachment->toArray(),
                $this->attachments,
            );
        }

        return $payload;
    }
}
