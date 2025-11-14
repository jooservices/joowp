<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\DTO;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * Base DTO helper that offers light validation for typed collections.
 */
abstract class DataTransferObject implements Arrayable
{
    protected static function assertArrayOf(string $className, array $items, string $argumentName): void
    {
        foreach ($items as $item) {
            if (! $item instanceof $className) {
                throw new InvalidArgumentException(sprintf(
                    '%s expects items of type %s, %s given.',
                    $argumentName,
                    $className,
                    is_object($item) ? $item::class : gettype($item)
                ));
            }
        }
    }
}
