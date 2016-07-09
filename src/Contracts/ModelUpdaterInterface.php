<?php
namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\ModelSaveFailureException;
use Illuminate\Database\Eloquent\Model;

interface ModelUpdaterInterface extends NestedParserInterface, TracksTemporaryIdsInterface
{
    
    /**
     * Creates a new model with (potential) nested data
     *
     * @param array $data
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function create(array $data);

    /**
     * Updates an existing model with (potential) nested update data
     *
     * @param array       $data
     * @param mixed|Model $model        either an existing model or its ID
     * @param string      $attribute    lookup column, if not primary key, only if $model is int
     * @param array       $saveOptions  options to pass on to the save() Eloquent method
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function update(array $data, $model, $attribute = null, array $saveOptions = []);

}
