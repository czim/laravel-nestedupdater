<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Container for information about a single temporary ID's status.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class TemporaryId
{
    /**
     * Wether the model has been created for this temporary ID.
     *
     * @var bool
     */
    protected bool $created = false;

    /**
     * The data to use to create the model.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $data = null;

    /**
     * The created model, if it is created.
     *
     * @var TModel|null
     */
    protected ?Model $model = null;

    /**
     * The model class FQN, if it is known.
     *
     * @var class-string<TModel>|null
     */
    protected ?string $modelClass = null;

    /**
     * Whether any of the temporary ID usages allow the model to be created.
     * This should be true if ANY of the nested usages allow this; all the others
     * may be treated as linking the model created only once.
     *
     * @var bool
     */
    protected bool $allowedToCreate = false;


    /**
     * Sets whether the model was created.
     *
     * @param bool $created
     * @return $this
     */
    public function setCreated(bool $created = true): static
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Returns whether the model has been created so far.
     *
     * @return bool
     */
    public function isCreated(): bool
    {
        return $this->created;
    }

    /**
     * Marks whether the temporary ID's data may be used to create anything.
     *
     * @param bool $allowedToCreate
     * @return $this
     */
    public function setAllowedToCreate(bool $allowedToCreate = true): static
    {
        $this->allowedToCreate = $allowedToCreate;

        return $this;
    }

    /**
     * Returns whether the temporary ID is allowed to be created at any point.
     *
     * @return bool
     */
    public function isAllowedToCreate(): bool
    {
        return $this->allowedToCreate;
    }

    /**
     * @param array<string, mixed> $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param TModel $model
     * @return $this
     */
    public function setModel(Model $model): static
    {
        $this->model = $model;

        if ($model->exists) {
            $this->created = true;
        }

        return $this;
    }

    /**
     * @return TModel|null
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * @param class-string<TModel>|null $class
     * @return $this
     */
    public function setModelClass(?string $class): static
    {
        $this->modelClass = $class;

        return $this;
    }

    /**
     * @return class-string<TModel>|null
     */
    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }
}
