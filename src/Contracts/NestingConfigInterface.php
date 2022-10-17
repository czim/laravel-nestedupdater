<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;

/**
 * @template TParent of \Illuminate\Database\Eloquent\Model
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface NestingConfigInterface
{
    /**
     * Sets the parent model FQN to be used if not explicitly provided
     * in other methods
     *
     * @param class-string<TParent> $parentModel   FQN of the parent model
     * @return $this
     */
    public function setParentModel(string $parentModel): static;

    /**
     * Returns a container with information about the nested relation by key
     *
     * @param string                     $key
     * @param null|class-string<TParent> $parentModel the FQN for the parent model
     * @return RelationInfo<TModel>
     */
    public function getRelationInfo(string $key, ?string $parentModel = null): RelationInfo;

    /**
     * Returns the FQN for the ModelUpdater to be used for a specific nested relation key
     *
     * @param string                     $key
     * @param null|class-string<TParent> $parentModel the FQN for the parent model
     * @return string
     */
    public function getUpdaterClassForKey(string $key, ?string $parentModel = null): string;

    /**
     * Returns whether a key, for the given model, is a nested relation at all.
     *
     * @param string                     $key
     * @param null|class-string<TParent> $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyNestedRelation(string $key, ?string $parentModel = null): bool;

    /**
     * Returns whether a key, for the given model, is an updateable nested relation.
     *
     * Updatable relations are relations that may have their contents updated through
     * the nested update operation. This returns false if related models may only be
     * linked, but not modified.
     *
     * @param string                     $key
     * @param null|class-string<TParent> $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyUpdatableNestedRelation(string $key, ?string $parentModel = null): bool;

    /**
     * Returns whether a key, for the given model, is a nested relation for which
     * new models may be created.
     *
     * @param string                     $key
     * @param null|class-string<TParent> $parentModel the FQN for the parent model
     * @return bool
     */
    public function isKeyCreatableNestedRelation(string $key, ?string $parentModel = null): bool;
}
