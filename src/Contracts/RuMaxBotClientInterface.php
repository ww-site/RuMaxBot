<?php

declare(strict_types=1);

namespace RuMaxBot\Contracts;

use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\DataTransferObjects\SendMessageResponseData;
use RuMaxBot\Exceptions\RuMaxBotException;

interface RuMaxBotClientInterface
{
    /**
     * @throws RuMaxBotException
     */
    public function send(SendMessageData $message): SendMessageResponseData;
}
