<?php

declare(strict_types=1);

namespace RuMaxBot\Enums;

enum MessageStatus: string
{
    case Queued = 'queued';

    case Answered = 'answered';

    case Failed = 'failed';
}
