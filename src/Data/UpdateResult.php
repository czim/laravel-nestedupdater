<?php

namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Container for the results of a nested update or create operation
 */
class UpdateResult
{
    /**
     * @var null|Model
     */
    protected $model;

    /**
     * Wether the operation was succesful.
     * If no model is set, but the operation is succesful, no exceptions should
     * be thrown, the relation should be assumed deliberately discarded.
     *
     * @var bool
     */
    protected $success = true;


    /**
     * @param Model|null $model
     * @return $this
     */
    public function setModel(?Model $model): UpdateResult
    {
        $this->model = $model;

        return $this;
    }

    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * @param boolean $success
     * @return $this
     */
    public function setSuccess(bool $success): UpdateResult
    {
        $this->success = $success;

        return $this;
    }

    public function success(): bool
    {
        return $this->success;
    }
}
