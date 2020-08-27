<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;

interface TracksTemporaryIdsInterface
{
    /**
     * Returns whether temporary ID handling is enabled.
     *
     * @return boolean
     */
    public function isHandlingTemporaryIds(): bool;

    /**
     * Stores TemporaryIds container.
     *
     * @param TemporaryIdsInterface $ids
     * @return $this
     */
    public function setTemporaryIds(TemporaryIdsInterface $ids): TracksTemporaryIdsInterface;

    /**
     * @return TemporaryIdsInterface|null
     */
    public function getTemporaryIds(): ?TemporaryIdsInterface;

    /**
     * Returns RelationInfo instance for nested data element by dot notation data key.
     *
     * @param string $key
     * @return RelationInfo|false     false if data could not be determined
     */
    public function getRelationInfoForDataKeyInDotNotation(string $key);
}
