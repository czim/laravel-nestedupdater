<?php

namespace Czim\NestedModelUpdater\Exceptions;

use Exception;

/**
 * Whenever an attempt is made to create (or update) a nested model when
 * this is not allowed to, f.i. because only linking/unlinking is allowed.
 */
class DisallowedNestedActionException extends Exception
{
    use StoresNestedKeyTrait;

}
