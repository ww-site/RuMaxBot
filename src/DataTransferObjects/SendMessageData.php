<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

use RuMaxBot\Enums\MessageFormat;

final readonly class SendMessageData
{
    public function __construct(
        public string $text,
        public MessageFormat $format = MessageFormat::Markdown,
    ) {}

    /**
     * @return array{text: string, format: string}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'format' => $this->format->value,
        ];
    }
}
