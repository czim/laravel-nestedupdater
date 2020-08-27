<?php

namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Container for information about a single temporary ID's status.
 */
class TemporaryId
{
    /**
     * Wether the model has been created for this temporary ID
     *
     * @var boolean
     */
    protected $created = false;

    /**
     * The data to use to create the model
     *
     * @var null|array
     */
    protected $data;

    /**
     * The created model, if it is created
     *
     * @var null|Model
     */
    protected $model;

    /**
     * The model class FQN, if it is known
     *
     * @var null|string
     */
    protected $modelClass;

    /**
     * Whether any of the temporary ID usages allow the model to be created.
     * This should be true if ANY of the nested usages allow this; all the others
     * may be treated as linking the model created only once.
     *
     * @var boolean
     */
    protected $allowedToCreate = false;


    /**
     * Sets whether the model was created.
     *
     * @param boolean $created
     * @return $this
     */
    public function setCreated(bool $created = true): TemporaryId
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns whether the model has been created so far.
     *
     * @return boolean
     */
    public function isCreated(): bool
    {
        return $this->created;
    }

    /**
     * Marks whether the temporary ID's data may be used to create anything.
     *
     * @param boolean $allowedToCreate
     * @return $this
     */
    public function setAllowedToCreate(bool $allowedToCreate = true): TemporaryId
    {
        $this->allowedToCreate = $allowedToCreate;

        return $this;
    }

    /**
     * Returns whether the temporary ID is allowed to be created at any point.
     *
     * @return boolean
     */
    public function isAllowedToCreate(): bool
    {
        return $this->allowedToCreate;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data): TemporaryId
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param Model|null $model
     * @return $this
     */
    public function setModel(Model $model): TemporaryId
    {
        $this->model = $model;

        if ($model->exists) {
            $this->created = true;
        }

        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * @param string|null $class
     * @return $this
     */
    public function setModelClass(?string $class): TemporaryId
    {
        $this->modelClass = $class;

        return $this;
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
