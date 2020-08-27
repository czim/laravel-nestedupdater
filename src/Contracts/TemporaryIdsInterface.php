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
    public function getKeys(): array;

    /**
     * @param string $key
     * @return bool
     */
    public function hasId(string $key): bool;

    /**
     * @param string $key
     * @return boolean
     */
    public function isCreatedForKey(string $key): bool;

    /**
     * Gets nested data set for given temporary ID.
     *
     * @param string $key
     * @return null|array
     */
    public function getDataForId(string $key): ?array;

    /**
     * Sets (nested) data for a given temporary ID key.
     *
     * @param string $key
     * @param array  $data
     * @return $this
     */
    public function setDataForId(string $key, array $data): TemporaryIdsInterface;

    /**
     * Returns the model class associated with a temporary ID.
     *
     * @param string $key
     * @return null|string
     */
    public function getModelClassForId(string $key): ?string;

    /**
     * Sets the model class associated with a temporary ID.
     *
     * @param string $key
     * @param string $class
     * @return $this
     */
    public function setModelClassForId(string $key, string $class): TemporaryIdsInterface;

    /**
     * Gets created model instance set for given temporary ID.
     *
     * @param string $key
     * @return null|Model
     */
    public function getModelForId(string $key): ?Model;

    /**
     * Sets a (created) model for a temporary ID.
     *
     * @param string $key
     * @param Model  $model
     * @return $this
     */
    public function setModelForId(string $key, Model $model): TemporaryIdsInterface;

    /**
     * Marks whether the model for a given temporary may be created.
     *
     * @param string $key
     * @param bool   $allowed
     * @return $this
     */
    public function markAllowedToCreateForId(string $key, bool $allowed = true): TemporaryIdsInterface;

    /**
     * Returns whether create is allowed for a given temporary ID.
     *
     * @param string $key
     * @return bool
     */
    public function isAllowedToCreateForId(string $key): bool;
}
