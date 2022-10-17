<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Container for the results of a nested update or create operation
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class UpdateResult
{
    /**
     * @var null|TModel
     */
    protected ?Model $model = null;

    /**
     * Wether the operation was successful.
     * If no model is set, but the operation is successful, no exceptions should
     * be thrown, the relation should be assumed deliberately discarded.
     *
     * @var bool
     */
    protected bool $success = true;


    /**
     * @param TModel|null $model
     * @return $this
     */
    public function setModel(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function success(): bool
    {
        return $this->success;
    }
}
