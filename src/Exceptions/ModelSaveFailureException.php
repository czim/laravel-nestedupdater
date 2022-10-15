<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Exceptions;

use RuntimeException;

/**
 * Hard failure when attempting to persist a model.
 */
class ModelSaveFailureException extends RuntimeException
{
    use StoresNestedKeyTrait;
}
