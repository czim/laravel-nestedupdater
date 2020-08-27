<?php

namespace Czim\NestedModelUpdater\Exceptions;

use Exception;

/**
 * Hard failure when attempting to persist a model.
 */
class ModelSaveFailureException extends Exception
{
    use StoresNestedKeyTrait;
}
