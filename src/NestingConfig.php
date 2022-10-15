<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater;

use BadMethodCallException;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

/**
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @implements NestingConfigInterface<TParent>
 */
class NestingConfig implements NestingConfigInterface
{
    /**
     * @var class-string<TParent>|null
     */
    protected ?string $parentModel = null;

    /**
     * Sets the parent model FQN to be used if not explicitly provided
     * in other methods
     *
     * @param class-string<TParent> $parentModel FQN of the parent model
     * @return $this
     */
    public function setParentModel(string $parentModel): static
    {
        $this->parentModel = $parentModel;

        return $this;
    }

    /**
     * Returns a container with information about the nested relation by key
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return RelationInfo<TParent>
     */
    public function getRelationInfo(string $key, ?string $parentModel = null): RelationInfo
    {
        if (! $this->isKeyNestedRelation($key, $parentModel)) {
            throw new RuntimeException(
                "{$key} is not a nested relation, cannot gather data"
                . ' for model ' . ($parentModel ?: $this->parentModel)
            );
        }

        $parent = $this->makeParentModel($parentModel);

        $relationMethod = $this->getRelationMethod($key, $parentModel);
        $relation       = $parent->{$relationMethod}();

        return (new RelationInfo())
            ->setRelationMethod($relationMethod)
            ->setRelationClass(get_class($relation))
            ->setModel($this->getModelForRelation($relation))
            ->setSingular($this->isRelationSingular($relation))
            ->setBelongsTo($this->isRelationBelongsTo($relation))
            ->setUpdater($this->getUpdaterClassForKey($key, $parentModel))
            ->setUpdateAllowed($this->isKeyUpdatableNestedRelation($key, $parentModel))
            ->setCreateAllowed($this->isKeyCreatableNestedRelation($key, $parentModel))
            ->setDetachMissing($this->isKeyDetachingNestedRelation($key, $parentModel))
            ->setDeleteDetached($this->isKeyDeletingNestedRelation($key, $parentModel))
            ->setValidator($this->getValidatorClassForKey($key, $parentModel))
            ->setRulesClass($this->getRulesClassForKey($key, $parentModel))
            ->setRulesMethod($this->getRulesMethodForKey($key, $parentModel));
    }

    /**
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel
     * @return array<string, mixed>|false
     */
    public function getNestedRelationConfigByKey(string $key, ?string $parentModel = null): array|false
    {
        $parentModel = $parentModel ?: $this->parentModel;

        return Config::get('nestedmodelupdater.relations.' . $parentModel . '.' . $key, false);
    }

    /**
     * Returns whether a key, for the given model, is a nested relation at all.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyNestedRelation(string $key, ?string $parentModel = null): bool
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        return (bool) $config;
    }

    /**
     * Returns whether a key, for the given model, is an update able nested relation.
     *
     * Updatable relations are relations that may have their contents updated through
     * the nested update operation. This returns false if related models may only be
     * linked, but not modified.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyUpdatableNestedRelation(string $key, ?string $parentModel = null): bool
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if ($config === true) {
            return true;
        }

        if (! is_array($config)) {
            return false;
        }

        return ! Arr::get($config, 'link-only', false);
    }

    /**
     * Returns whether a key, for the given model, is a nested relation for which new models may be created.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyCreatableNestedRelation(string $key, ?string $parentModel = null): bool
    {
        if (! $this->isKeyUpdatableNestedRelation($key, $parentModel)) {
            return false;
        }

        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if ($config === true) {
            return true;
        }

        if (! is_array($config)) {
            return false;
        }

        return ! Arr::get($config, 'update-only', false);
    }

    /**
     * Returns whether a nested relation detaches missing records in update data.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return bool|null
     */
    public function isKeyDetachingNestedRelation(string $key, ?string $parentModel = null): ?bool
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (! is_array($config)) {
            return null;
        }

        $detach = Arr::get($config, 'detach');

        return null === $detach ? null : (bool) $detach;
    }

    /**
     * Returns whether a nested relation deletes detached missing records in update data.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyDeletingNestedRelation(string $key, ?string $parentModel = null): bool
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (! is_array($config)) {
            return false;
        }

        return (bool) Arr::get($config, 'delete-detached', false);
    }

    /**
     * Returns the name of the method on the parent model for the relation.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return string|false
     */
    public function getRelationMethod(string $key, ?string $parentModel = null): string|false
    {
        return $this->getStringValueForKey($key, 'method', Str::camel($key), $parentModel);
    }

    /**
     * Returns the FQN for the ModelUpdater to be used for a specific nested relation key
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return string
     */
    public function getUpdaterClassForKey(string $key, ?string $parentModel = null): string
    {
        return $this->getStringValueForKey($key, 'updater', ModelUpdaterInterface::class, $parentModel);
    }

    /**
     * Returns a fresh instance of the parent model for the relation.
     *
     * @param class-string<TParent>|null $parentClass
     * @return TParent
     */
    protected function makeParentModel(?string $parentClass = null): Model
    {
        $parentClass = $parentClass ?: $this->parentModel;

        if (! $parentClass) {
            throw new BadMethodCallException("Could not create parent model, no class name given.");
        }

        $model = new $parentClass;

        if (! $model instanceof Model) {
            throw new UnexpectedValueException("Expected Model for parentModel, got {$parentClass} instead.");
        }

        return $model;
    }

    /**
     * Returns FQN for related model.
     *
     * @param Relation<Model> $relation
     * @return Model
     */
    protected function getModelForRelation(Relation $relation): Model
    {
        return $relation->getRelated();
    }

    /**
     * Returns wether relation is of singular type.
     *
     * @param Relation<Model> $relation
     * @return bool
     */
    protected function isRelationSingular(Relation $relation): bool
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.singular-relations', []),
            true
        );
    }

    /**
     * Returns wether relation is of the 'belongs to' type (foreign key stored on the parent).
     *
     * @param Relation<Model> $relation
     * @return bool
     */
    protected function isRelationBelongsTo(Relation $relation): bool
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.belongs-to-relations', []),
            true
        );
    }

    /**
     * Returns a string relation config value for a given nested data key.
     *
     * @param string                     $key
     * @param string                     $configKey
     * @param string|null                $default
     * @param class-string<TParent>|null $parentModel
     * @return string|false
     */
    protected function getStringValueForKey(
        string $key,
        string $configKey,
        ?string $default = null,
        ?string $parentModel = null,
    ): string|false {

        if (! $this->isKeyNestedRelation($key, $parentModel)) {
            return false;
        }

        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (is_array($config) && Arr::has($config, $configKey)) {
            return Arr::get($config, $configKey);
        }

        return $default ?: false;
    }

    // ------------------------------------------------------------------------------
    //      Validation
    // ------------------------------------------------------------------------------

    /**
     * Returns the FQN for the nested validator to be used for a specific nested relation key.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return string
     */
    public function getValidatorClassForKey(string $key, ?string $parentModel = null): string
    {
        return $this->getStringValueForKey($key, 'validator', NestedValidatorInterface::class, $parentModel);
    }

    /**
     * Returns the FQN for the class that has the rules for the nested model.
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return string|null
     */
    public function getRulesClassForKey(string $key, ?string $parentModel = null): ?string
    {
        return $this->getStringValueForKey($key, 'rules', null, $parentModel);
    }

    /**
     * Returns the FQN for the method on the rules class that should be called to
     * get the rules array
     *
     * @param string                     $key
     * @param class-string<TParent>|null $parentModel the FQN for the parent model
     * @return string|null
     */
    public function getRulesMethodForKey(string $key, ?string $parentModel = null): ?string
    {
        return $this->getStringValueForKey($key, 'rules-method', null, $parentModel);
    }
}
