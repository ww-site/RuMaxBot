<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

use RuMaxBot\Enums\ButtonType;

final readonly class InlineButton
{
    public function __construct(
        public string $text,
        public ButtonType $type = ButtonType::Callback,
        public ?string $payload = null,
        public ?string $url = null,
    ) {}

    public static function callback(string $text, string $payload): self
    {
        return new self(
            text: $text,
            type: ButtonType::Callback,
            payload: $payload,
        );
    }

    public static function link(string $text, string $url): self
    {
        return new self(
            text: $text,
            type: ButtonType::Link,
            url: $url,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $button = [
            'type' => $this->type->value,
            'text' => $this->text,
        ];

        if ($this->type === ButtonType::Callback && $this->payload !== null) {
            $button['payload'] = $this->payload;
        }

        if ($this->type === ButtonType::Link && $this->url !== null) {
            $button['url'] = $this->url;
        }

        return $button;
    }
}
