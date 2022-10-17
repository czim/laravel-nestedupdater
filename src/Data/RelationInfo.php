<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Data;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Container for information about a given relation that the NestingConfig may determine and provide.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class RelationInfo
{
    /**
     * The name of the relation method
     *
     * @var string
     */
    protected string $relationMethod;

    /**
     * The FQN of the relation class returned by the relation method
     *
     * @var class-string<Relation<TModel>>
     */
    protected string $relationClass;

    /**
     * Whether the relationship is of a One, as opposed to a Many, type
     *
     * @var bool
     */
    protected bool $singular = true;

    /**
     * Whether the relationship is of the belongsTo type, that is, whether
     * the foreign key for this relation is stored on the main/parent model.
     *
     * @var bool
     */
    protected bool $belongsTo = false;

    /**
     * An instance of the child model for the relation
     *
     * @var TModel|null
     */
    protected ?Model $model = null;

    /**
     * The FQN of the ModelUpdater that should handle update or create process
     *
     * @var class-string<ModelUpdaterInterface>|null
     */
    protected ?string $updater = null;

    /**
     * Whether it is allowed to update data (and relations) of the nested related records.
     * If this is false, only (dis)connecting relationships should be allowed.
     *
     * @var bool
     */
    protected bool $updateAllowed = false;

    /**
     * Whether it is allowed to create nested records for this relation.
     *
     * @var bool
     */
    protected bool $createAllowed = false;

    /**
     * Whether missing records in a set of nested data should be detached.
     * If null, default is true for BelongsToMany and false for everything else.
     *
     * @var bool|null
     */
    protected ?bool $detachMissing = null;

    /**
     * Whether, if detachMissing is true, detached models should be deleted instead of merely dissociated.
     *
     * @var bool
     */
    protected bool $deleteDetached = false;

    /**
     * @var class-string<NestedValidatorInterface>|null FQN for nested validator that should handle nested validation
     */
    protected ?string $validator = null;

    /**
     * @var null|class-string FQN for the class that provides the rules for the model
     */
    protected ?string $rulesClass = null;

    /**
     * @var string|null name of the method that provides the array with rules
     */
    protected ?string $rulesMethod = null;


    public function relationMethod(): string
    {
        return $this->relationMethod;
    }

    /**
     * @param string $relationMethod
     * @return $this
     */
    public function setRelationMethod(string $relationMethod): static
    {
        $this->relationMethod = $relationMethod;

        return $this;
    }

    /**
     * @return class-string<Relation<TModel>>
     */
    public function relationClass(): string
    {
        return $this->relationClass;
    }

    /**
     * @param class-string<Relation<TModel>> $relationClass
     * @return $this
     */
    public function setRelationClass(string $relationClass): static
    {
        $this->relationClass = $relationClass;

        return $this;
    }

    public function isSingular(): bool
    {
        return $this->singular;
    }

    /**
     * @param bool $singular
     * @return $this
     */
    public function setSingular(bool $singular): static
    {
        $this->singular = $singular;

        return $this;
    }

    public function isBelongsTo(): bool
    {
        return $this->belongsTo;
    }

    /**
     * @param bool $belongsTo
     * @return $this
     */
    public function setBelongsTo(bool $belongsTo): static
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }

    /**
     * @return TModel|null
     */
    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * @param null|TModel $model
     * @return $this
     */
    public function setModel(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return class-string<ModelUpdaterInterface>|null
     */
    public function updater(): ?string
    {
        return $this->updater;
    }

    /**
     * @param class-string<ModelUpdaterInterface>|null $updater
     * @return $this
     */
    public function setUpdater(?string $updater): static
    {
        $this->updater = $updater;

        return $this;
    }

    public function isUpdateAllowed(): bool
    {
        return $this->updateAllowed;
    }

    /**
     * @param bool $updateAllowed
     * @return $this
     */
    public function setUpdateAllowed(bool $updateAllowed): static
    {
        $this->updateAllowed = $updateAllowed;

        return $this;
    }

    public function isCreateAllowed(): bool
    {
        return $this->createAllowed;
    }

    /**
     * @param bool $createAllowed
     * @return $this
     */
    public function setCreateAllowed(bool $createAllowed): static
    {
        $this->createAllowed = $createAllowed;

        return $this;
    }

    public function isDeleteDetached(): bool
    {
        return $this->deleteDetached;
    }

    /**
     * @param bool $deleteDetached
     * @return $this
     */
    public function setDeleteDetached(bool $deleteDetached): static
    {
        $this->deleteDetached = $deleteDetached;

        return $this;
    }

    public function getDetachMissing(): ?bool
    {
        return $this->detachMissing;
    }

    /**
     * @param bool|null $detachMissing
     * @return $this
     */
    public function setDetachMissing(?bool $detachMissing): static
    {
        $this->detachMissing = $detachMissing;

        return $this;
    }

    /**]
     * @return class-string<NestedValidatorInterface>|null
     */
    public function validator(): ?string
    {
        return $this->validator;
    }

    /**
     * @param class-string<NestedValidatorInterface>|null $validator
     * @return $this
     */
    public function setValidator(?string $validator): RelationInfo
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function rulesClass(): ?string
    {
        return $this->rulesClass;
    }

    /**
     * @param class-string|null $rulesClass
     * @return $this
     */
    public function setRulesClass(?string $rulesClass): static
    {
        $this->rulesClass = $rulesClass;

        return $this;
    }

    public function rulesMethod(): ?string
    {
        return $this->rulesMethod;
    }

    /**
     * @param string|null $rulesMethod
     * @return $this
     */
    public function setRulesMethod(?string $rulesMethod): static
    {
        $this->rulesMethod = $rulesMethod;

        return $this;
    }
}
