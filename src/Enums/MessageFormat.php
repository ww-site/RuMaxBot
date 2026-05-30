<?php

declare(strict_types=1);

namespace RuMaxBot\Enums;

enum MessageFormat: string
{
    case Markdown = 'markdown';

    case Html = 'html';
}
