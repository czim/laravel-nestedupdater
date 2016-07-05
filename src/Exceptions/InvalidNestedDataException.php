<?php
namespace Czim\NestedModelUpdater\Exceptions;

use Exception;

/**
 * Class InvalidNestedDataException
 *
 * Whenever nested data is invalid and cannot be parsed correctly.
 */
class InvalidNestedDataException extends Exception
{
    use StoresNestedKeyTrait;
    
}
