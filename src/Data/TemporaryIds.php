<?php
namespace Czim\NestedModelUpdater\Data;

use Czim\NestedModelUpdater\Contracts\TemporaryIdsInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TemporaryIds
 * 
 * Container for information about temporary IDs used (so far) in the
 * update/create process.
 */
class TemporaryIds implements TemporaryIdsInterface
{

    /**
     * @var TemporaryId[]   assoc, keyed by temporary key attribute
     */
    protected $temporaryIds = [];

    /**
     * Returns all keys for temporary IDs.
     *
     * @return string[]
     */
    public function getKeys()
    {
        return array_keys($this->temporaryIds);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasId($key)
    {
        return array_key_exists($key, $this->temporaryIds);    
    }

    /**
     * Sets (nested) data for a given temporary ID key.
     *
     * @param string $key
     * @param array  $data
     * @return $this
     */
    public function setDataForId($key, array $data)
    {
        // do not overwrite if we have a model already
        if ($this->hasId($key) && $this->getByKey($key)->isCreated()) return $this;

        $this->getOrCreateByKey($key)->setData($data);
        
        return $this;
    }

    /**
     * Sets a (created) model for a temporary ID
     *
     * @param string $key
     * @param Model  $model
     * @return $this
     */
    public function setModelForId($key, Model $model)
    {
        $this->getOrCreateByKey($key)->setModel($model);

        return $this;
    }

    /**
     * Sets the model class associated with a temporary ID.
     *
     * @param string $key
     * @param string $class
     * @return $this
     */
    public function setModelClassForId($key, $class)
    {
        $this->getOrCreateByKey($key)->setModelClass($class);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function isCreatedForKey($key)
    {
        $temp = $this->getByKey($key);

        return $temp ? $temp->isCreated() : false;
    }

    /**
     * Gets nested data set for given temporary ID.
     *
     * @param string $key
     * @return null|array
     */
    public function getDataForId($key)
    {
        $temp = $this->getByKey($key);

        return $temp ? $temp->getData() : null;
    }

    /**
     * Gets created model instance set for given temporary ID.
     *
     * @param string $key
     * @return null|Model
     */
    public function getModelForId($key)
    {
        $temp = $this->getByKey($key);

        return $temp ? $temp->getModel() : null;
    }

    /**
     * Returns the model class associated with a temporary ID.
     *
     * @param string $key
     * @return null|string
     */
    public function getModelClassForId($key)
    {
        $temp = $this->getByKey($key);

        return $temp ? $temp->getModelClass() : null;
    }

    /**
     * Returns temporary ID container for a given key
     *
     * @param string $key
     * @return null|TemporaryId
     */
    protected function getByKey($key)
    {
        if ( ! $this->hasId($key)) return null;

        return $this->temporaryIds[$key];
    }

    /**
     * Returns temporary ID container or creates a new container if it does not exist.
     *
     * @param $key
     * @return TemporaryId
     */
    protected function getOrCreateByKey($key)
    {
        $temporaryId = $this->getByKey($key);

        if ( ! $temporaryId) {
            $temporaryId = $this->temporaryIds[$key] = new TemporaryId;
        }

        return $temporaryId;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->temporaryIds;
    }
    
}
