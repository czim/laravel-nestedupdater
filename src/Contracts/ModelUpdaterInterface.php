<?php
namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Illuminate\Database\Eloquent\Model;

interface ModelUpdaterInterface
{

    /**
     * Creates a new model with (potential) nested data
     *
     * @param array $data
     */
    public function create(array $data);

    /**
     * Updates an existing model with (potential) nested update data
     *
     * @param array     $data
     * @param int|Model $model      either an existing model or its ID
     * @param string    $attribute  lookup column, if not primary key, only if $model is int
     * @return UpdateResult
     */
    public function update(array $data, $model, $attribute = null);

}
