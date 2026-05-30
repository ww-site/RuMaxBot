<?php

declare(strict_types=1);

namespace RuMaxBot\DataTransferObjects;

final readonly class SendMessageResponseData
{
    public function __construct(
        public string $status,
        public ?string $id = null,
        public ?string $message = null,
    ) {}
}
