<?php
namespace Czim\NestedModelUpdater\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TemporaryIdsInterface
{

    /**
     * Returns all keys for temporary IDs.
     *
     * @return string[]
     */
    public function getKeys();

    /**
     * @param string $key
     * @return bool
     */
    public function hasId($key);

    /**
     * @param string $key
     * @return boolean
     */
    public function isCreatedForKey($key);

    /**
     * Gets nested data set for given temporary ID.
     *
     * @param string $key
     * @return null|array
     */
    public function getDataForId($key);

    /**
     * Sets (nested) data for a given temporary ID key.
     *
     * @param string $key
     * @param array  $data
     * @return $this
     */
    public function setDataForId($key, array $data);

    /**
     * Returns the model class associated with a temporary ID.
     *
     * @param string $key
     * @return null|string
     */
    public function getModelClassForId($key);

    /**
     * Sets the model class associated with a temporary ID.
     *
     * @param string $key
     * @param string $class
     * @return $this
     */
    public function setModelClassForId($key, $class);

    /**
     * Gets created model instance set for given temporary ID.
     *
     * @param string $key
     * @return null|Model
     */
    public function getModelForId($key);

    /**
     * Sets a (created) model for a temporary ID.
     *
     * @param string $key
     * @param Model  $model
     * @return $this
     */
    public function setModelForId($key, Model $model);

    /**
     * Marks whether the model for a given temporary may be created.
     *
     * @param string $key
     * @param bool   $allowed
     * @return $this
     */
    public function markAllowedToCreateForId($key, $allowed = true);

    /**
     * Returns whether create is allowed for a given temporary ID.
     *
     * @param string $key
     * @return bool
     */
    public function isAllowedToCreateForId($key);
    
}
