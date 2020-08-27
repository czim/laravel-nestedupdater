<?php

namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Container for information about a given relation that the NestingConfig may
 * determine and provide.
 */
class RelationInfo
{
    /**
     * The name of the relation method
     *
     * @var string
     */
    protected $relationMethod;

    /**
     * The FQN of the relation class returned by the relation method
     *
     * @var string
     */
    protected $relationClass;

    /**
     * Whether the relationship is of a One, as opposed to a Many, type
     *
     * @var bool
     */
    protected $singular = true;

    /**
     * Whether the relationship is of the belongsTo type, that is, whether
     * the foreign key for this relation is stored on the main/parent model.
     *
     * @var bool
     */
    protected $belongsTo = false;

    /**
     * An instance of the child model for the relation
     *
     * @var Model|null
     */
    protected $model;

    /**
     * The FQN of the ModelUpdater that should handle update or create process
     *
     * @var string|null
     */
    protected $updater;

    /**
     * Whether it is allowed to update data (and relations) of the nested related
     * records. If this is false, only (dis)connecting relationships should be
     * allowed.
     *
     * @var boolean
     */
    protected $updateAllowed = false;

    /**
     * Whether it is allowed to create nested records for this relation.
     *
     * @var boolean
     */
    protected $createAllowed = false;

    /**
     * Whether missing records in a set of nested data should be detached.
     * If null, default is true for BelongsToMany and false for everything else.
     *
     * @var null|boolean
     */
    protected $detachMissing;

    /**
     * Whether, if detachMissing is true, detached models should be deleted
     * instead of merely dissociated.
     *
     * @var boolean
     */
    protected $deleteDetached = false;

    /**
     * @var null|string     FQN for nested validator that should handle nested validation
     */
    protected $validator;

    /**
     * @var null|string     FQN for the class that provides the rules for the model
     */
    protected $rulesClass;

    /**
     * @var null|string     name of the method that provides the array with rules
     */
    protected $rulesMethod;


    public function relationMethod(): string
    {
        return $this->relationMethod;
    }

    /**
     * @param string $relationMethod
     * @return $this
     */
    public function setRelationMethod(string $relationMethod): RelationInfo
    {
        $this->relationMethod = $relationMethod;

        return $this;
    }

    public function relationClass(): string
    {
        return $this->relationClass;
    }

    /**
     * @param string $relationClass
     * @return $this
     */
    public function setRelationClass($relationClass): RelationInfo
    {
        $this->relationClass = $relationClass;

        return $this;
    }

    public function isSingular(): bool
    {
        return $this->singular;
    }

    /**
     * @param boolean $singular
     * @return $this
     */
    public function setSingular(bool $singular): RelationInfo
    {
        $this->singular = $singular;

        return $this;
    }

    public function isBelongsTo(): bool
    {
        return $this->belongsTo;
    }

    /**
     * @param boolean $belongsTo
     * @return $this
     */
    public function setBelongsTo(bool $belongsTo): RelationInfo
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }

    public function model(): ?Model
    {
        return $this->model;
    }

    /**
     * @param null|Model $model
     * @return $this
     */
    public function setModel(?Model $model): RelationInfo
    {
        $this->model = $model;

        return $this;
    }

    public function updater(): ?string
    {
        return $this->updater;
    }

    /**
     * @param null|string $updater
     * @return $this
     */
    public function setUpdater(?string $updater): RelationInfo
    {
        $this->updater = $updater;

        return $this;
    }

    public function isUpdateAllowed(): bool
    {
        return $this->updateAllowed;
    }

    /**
     * @param boolean $updateAllowed
     * @return $this
     */
    public function setUpdateAllowed(bool $updateAllowed): RelationInfo
    {
        $this->updateAllowed = $updateAllowed;

        return $this;
    }

    public function isCreateAllowed(): bool
    {
        return $this->createAllowed;
    }

    /**
     * @param boolean $createAllowed
     * @return $this
     */
    public function setCreateAllowed(bool $createAllowed): RelationInfo
    {
        $this->createAllowed = $createAllowed;

        return $this;
    }

    public function isDeleteDetached(): bool
    {
        return $this->deleteDetached;
    }

    /**
     * @param boolean $deleteDetached
     * @return $this
     */
    public function setDeleteDetached(bool $deleteDetached): RelationInfo
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
    public function setDetachMissing(?bool $detachMissing): RelationInfo
    {
        $this->detachMissing = $detachMissing;

        return $this;
    }

    public function validator(): ?string
    {
        return $this->validator;
    }

    /**
     * @param string $validator
     * @return $this
     */
    public function setValidator(?string $validator): RelationInfo
    {
        $this->validator = $validator;

        return $this;
    }

    public function rulesClass(): ?string
    {
        return $this->rulesClass;
    }

    /**
     * @param string|null $rulesClass
     * @return $this
     */
    public function setRulesClass(?string $rulesClass): RelationInfo
    {
        $this->rulesClass = $rulesClass;

        return $this;
    }

    public function rulesMethod(): ?string
    {
        return $this->rulesMethod;
    }

    /**
     * @param null|string $rulesMethod
     * @return $this
     */
    public function setRulesMethod(?string $rulesMethod): RelationInfo
    {
        $this->rulesMethod = $rulesMethod;

        return $this;
    }
}
