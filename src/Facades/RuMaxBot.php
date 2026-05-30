<?php

declare(strict_types=1);

namespace RuMaxBot\Facades;

use Illuminate\Support\Facades\Facade;
use RuMaxBot\Contracts\RuMaxBotClientInterface;
use RuMaxBot\DataTransferObjects\SendMessageData;
use RuMaxBot\DataTransferObjects\SendMessageResponseData;

/**
 * @method static SendMessageResponseData send(SendMessageData $message)
 *
 * @see RuMaxBotClientInterface
 */
final class RuMaxBot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RuMaxBotClientInterface::class;
    }
}
