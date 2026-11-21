<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

final readonly class InlineKeyboardAttachment
{
    /**
     * @param list<list<InlineButton>> $rows
     */
    public function __construct(
        public array $rows,
    ) {}

    /**
     * @param list<list<InlineButton>> $rows
     */
    public static function make(array $rows): self
    {
        return new self($rows);
    }

    /**
     * @return array{type: string, payload: array{buttons: list<list<array<string, string>>>}}
     */
    public function toArray(): array
    {
        return [
            'type' => 'inline_keyboard',
            'payload' => [
                'buttons' => array_map(
                    static fn (array $row): array => array_map(
                        static fn (InlineButton $button): array => $button->toArray(),
                        $row,
                    ),
                    $this->rows,
                ),
            ],
        ];
    }
}
