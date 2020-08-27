<?php

namespace Czim\NestedModelUpdater\Exceptions;

use Exception;

/**
 * Whenever nested data is invalid and cannot be parsed correctly.
 */
class InvalidNestedDataException extends Exception
{
    use StoresNestedKeyTrait;
}
