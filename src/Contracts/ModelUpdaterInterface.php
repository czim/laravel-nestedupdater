<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\ModelSaveFailureException;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @extends NestedParserInterface<TModel, TParent>
 */
interface ModelUpdaterInterface extends
    NestedParserInterface,
    TracksTemporaryIdsInterface,
    HandlesUnguardedAttributesInterface
{
    /**
     * Creates a new model with (potential) nested data
     *
     * @param array<string, mixed> $data
     * @return UpdateResult<TModel>
     * @throws ModelSaveFailureException
     */
    public function create(array $data): UpdateResult;

    /**
     * Force creates a new model with (potential) nested data
     *
     * @param array<string, mixed> $data
     * @return UpdateResult<TModel>
     * @throws ModelSaveFailureException
     */
    public function forceCreate(array $data): UpdateResult;

    /**
     * Updates an existing model with (potential) nested update data
     *
     * @param array<string, mixed> $data
     * @param int|string|TModel    $model       either an existing model or its ID
     * @param string|null          $attribute   lookup column, if not primary key, only if $model is int
     * @param array<string, mixed> $saveOptions options to pass on to the save() Eloquent method
     * @return UpdateResult<TModel>
     * @throws ModelSaveFailureException
     */
    public function update(
        array $data,
        int|string|Model $model,
        string $attribute = null,
        array $saveOptions = [],
    ): UpdateResult;

    /**
     * Force updates an existing model with (potential) nested update data.
     *
     * @param array<string, mixed> $data
     * @param int|string|TModel    $model       either an existing model or its ID
     * @param string|null          $attribute   lookup column, if not primary key, only if $model is int
     * @param array<string, mixed> $saveOptions options to pass on to the save() Eloquent method
     * @return UpdateResult<TModel>
     * @throws ModelSaveFailureException
     */
    public function forceUpdate(
        array $data,
        int|string|Model $model,
        ?string $attribute = null,
        array $saveOptions = [],
    ): UpdateResult;

    /**
     * Sets the forceFill property on the current instance.
     *
     * When set to true, forceFill() will be used to set attributes on the model, rather than the regular fill(),
     * which takes guarded attributes into consideration.
     *
     * @param bool $force
     * @return $this
     */
    public function force(bool $force): static;
}
