<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO\Chat;

enum ChatRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
