<?php
namespace Czim\NestedModelUpdater;

use BadMethodCallException;
use Config;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class NestingConfig implements NestingConfigInterface
{

    /**
     * @var null|string
     */
    protected $parentModel;

    /**
     * Sets the parent model FQN to be used if not explicitly provided
     * in other methods
     *
     * @param string $parentModel FQN of the parent model
     * @return $this
     */
    public function setParentModel($parentModel)
    {
        $this->parentModel = $parentModel;
        
        return $this;
    }

    /**
     * Returns a container with information about the nested relation by key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return RelationInfo
     */
    public function getRelationInfo($key, $parentModel = null)
    {
        if ( ! $this->isKeyNestedRelation($key, $parentModel)) {
            throw new RuntimeException(
                "{$key} is not a nested relation, cannot gather data"
                . " for model " . ($parentModel ?: $this->parentModel)
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
     * @param string      $key
     * @param null|string $parentModel
     * @return array|boolean
     */
    public function getNestedRelationConfigByKey($key, $parentModel = null)
    {
        $parentModel = $parentModel ?: $this->parentModel;

        return Config::get('nestedmodelupdater.relations.' . $parentModel . '.' . $key, false);
    }

    /**
     * Returns whether a key, for the given model, is a nested relation at all.
     *
     * @param string      $key
     * @param null|string $parentModel      the FQN for the parent model
     * @return boolean
     */
    public function isKeyNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        return (false !== $config && null !== $config);
    }

    /**
     * Returns whether a key, for the given model, is an updatable nested relation.
     * Updatable relations are relations that may have their contents updated through
     * the nested update operation. This returns false if related models may only be
     * linked, but not modified.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return boolean
     */
    public function isKeyUpdatableNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);
        
        if (true === $config) return true;
        if ( ! is_array($config)) return false;
        
        return ! (bool) Arr::get($config, 'link-only', false);
    }

    /**
     * Returns whether a key, for the given model, is a nested relation for which
     * new models may be created.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return boolean
     */
    public function isKeyCreatableNestedRelation($key, $parentModel = null)
    {
        if ( ! $this->isKeyUpdatableNestedRelation($key, $parentModel)) {
            return false;
        }

        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (true === $config) return true;
        if ( ! is_array($config)) return false;

        return ! (bool) Arr::get($config, 'update-only', false);
    }

    /**
     * Returns whether a nested relation detaches missing records in update data.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return null|boolean
     */
    public function isKeyDetachingNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (true === $config || ! is_array($config)) return null;

        $detach = Arr::get($config, 'detach', null);

        return null === $detach ? null : (bool) $detach;
    }

    /**
     * Returns whether a nested relation deletes detached missing records in update data.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return boolean
     */
    public function isKeyDeletingNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        if (true === $config || ! is_array($config)) return false;

        return (bool) Arr::get($config, 'delete-detached', false);
    }

    /**
     * Returns the name of the method on the parent model for the relation.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string|false
     */
    public function getRelationMethod($key, $parentModel = null)
    {
        return $this->getStringValueForKey($key, 'method', Str::camel($key), $parentModel);
    }

    /**
     * Returns the FQN for the ModelUpdater to be used for a specific nested relation key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string
     */
    public function getUpdaterClassForKey($key, $parentModel = null)
    {
        return $this->getStringValueForKey($key, 'updater', ModelUpdater::class, $parentModel);
    }

    /**
     * Returns a fresh instance of the parent model for the relation.
     *
     * @param null|string $parentClass
     * @return Model
     */
    protected function makeParentModel($parentClass = null)
    {
        $parentClass = $parentClass ?: $this->parentModel;

        if ( ! $parentClass) {
            throw new BadMethodCallException("Could not create parent model, no class name given.");
        }

        $model = new $parentClass;

        if ( ! ($model instanceof Model)) {
            throw new UnexpectedValueException("Expected Model for parentModel, got {$parentClass} instead.");
        }

        return $model;
    }

    /**
     * Returns the Relation object returned by calling the relation method on a model. 
     * 
     * @param Model  $model
     * @param string $method
     * @return Relation
     */
    protected function makeRelation(Model $model, $method)
    {
        if ( ! method_exists($model, $method)) {
            throw new UnexpectedValueException(
                "Relation method '{$method}' on model " . get_class($model) . ' does not exist'
            );  
        }
        
        $relation = $model->{$method};
        
        if ( ! ($relation instanceof Relation)) {
            throw new UnexpectedValueException(
                "Method '{$method}' on model " . get_class($model) . ' did not return a Relation instance'
            );    
        }
        
        return $relation;
    }

    /**
     * Returns FQN for related model.
     *
     * @param Relation $relation
     * @return Model
     */
    protected function getModelForRelation(Relation $relation)
    {
        return $relation->getRelated();
    }

    /**
     * Returns primary key attribute for related model.
     *
     * @param Relation $relation
     * @return string
     */
    protected function determinePrimaryKeyForRelation(Relation $relation)
    {
        return $this->getModelForRelation($relation)->getKeyName();
    }

    /**
     * Returns wether relation is of singular type.
     *
     * @param Relation $relation
     * @return bool
     */
    protected function isRelationSingular(Relation $relation)
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.singular-relations', [])
        );
    }

    /**
     * Returns wether relation is of the 'belongs to' type (foreign key
     * stored on the parent).
     *
     * @param Relation $relation
     * @return bool
     */
    protected function isRelationBelongsTo(Relation $relation)
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.belongs-to-relations', [])
        );
    }

    /**
     * Returns a string relation config value for a given nested data key
     *
     * @param string      $key
     * @param string      $configKey
     * @param string      $default
     * @param null|string $parentModel
     * @return bool|string
     */
    protected function getStringValueForKey($key, $configKey, $default = null, $parentModel = null)
    {
        if ( ! $this->isKeyNestedRelation($key, $parentModel)) {
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
     * Returns the FQN for the nested validator to be used for a specific nested relation key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string
     */
    public function getValidatorClassForKey($key, $parentModel = null)
    {
        return $this->getStringValueForKey($key, 'validator', NestedValidator::class, $parentModel);
    }

    /**
     * Returns the FQN for the class that has the rules for the nested model
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string
     */
    public function getRulesClassForKey($key, $parentModel = null)
    {
        return $this->getStringValueForKey($key, 'rules', null, $parentModel);
    }

    /**
     * Returns the FQN for the method on the rules class that should be called to
     * get the rules array
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string
     */
    public function getRulesMethodForKey($key, $parentModel = null)
    {
        $default = config('nestedmodelupdater.validation.model-rules-method', 'rules');

        return $this->getStringValueForKey($key, 'rules-method', $default, $parentModel);
    }

}
