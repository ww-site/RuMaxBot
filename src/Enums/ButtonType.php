<?php

declare(strict_types=1);

namespace RuMaxBot\Enums;

enum ButtonType: string
{
    case Callback = 'callback';

    case Link = 'link';
}
