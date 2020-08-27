<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;

interface NestingConfigInterface
{
    /**
     * Sets the parent model FQN to be used if not explicitly provided
     * in other methods
     *
     * @param string $parentModel   FQN of the parent model
     * @return $this
     */
    public function setParentModel(string $parentModel): NestingConfigInterface;

    /**
     * Returns a container with information about the nested relation by key
     *
     * @param string       $key
     * @param null|string  $parentModel     the FQN for the parent model
     * @return RelationInfo
     */
    public function getRelationInfo(string $key, ?string $parentModel = null): RelationInfo;

    /**
     * Returns the FQN for the ModelUpdater to be used for a specific nested relation key
     *
     * @param string      $key
     * @param null|string $parentModel      the FQN for the parent model
     * @return string
     */
    public function getUpdaterClassForKey(string $key, ?string $parentModel = null): string;

    /**
     * Returns whether a key, for the given model, is a nested relation at all.
     *
     * @param string      $key
     * @param null|string $parentModel      the FQN for the parent model
     * @return boolean
     */
    public function isKeyNestedRelation(string $key, ?string $parentModel = null): bool;

    /**
     * Returns whether a key, for the given model, is an updatable nested relation.
     * Updatable relations are relations that may have their contents updated through
     * the nested update operation. This returns false if related models may only be
     * linked, but not modified.
     *
     * @param string      $key
     * @param null|string $parentModel      the FQN for the parent model
     * @return boolean
     */
    public function isKeyUpdatableNestedRelation(string $key, ?string $parentModel = null): bool;

    /**
     * Returns whether a key, for the given model, is a nested relation for which
     * new models may be created.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return boolean
     */
    public function isKeyCreatableNestedRelation(string $key, ?string $parentModel = null): bool;
}
