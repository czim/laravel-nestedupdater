<?php

namespace Czim\NestedModelUpdater\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * When a model referred to in the nested update data cannot be found by id
 */
class NestedModelNotFoundException extends ModelNotFoundException
{
    use StoresNestedKeyTrait;
}
