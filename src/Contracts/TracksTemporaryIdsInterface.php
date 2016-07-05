<?php
namespace Czim\NestedModelUpdater\Contracts;

interface TracksTemporaryIdsInterface
{

    /**
     * Returns whether temporary ID handling is enabled.
     *
     * @return boolean
     */
    public function isHandlingTemporaryIds();

    /**
     * Stores TemporaryIds container.
     *
     * @param TemporaryIdsInterface $ids
     * @return $this
     */
    public function setTemporaryIds(TemporaryIdsInterface $ids);

    /**
     * @return TemporaryIdsInterface|null
     */
    public function getTemporaryIds();

    /**
     * Returns FQN for model related to the dot-notation key in the data array.
     *
     * @param string $key
     * @return string|false     false if model could not be determined
     */
    public function getModelClassForDataKeyInDotNotation($key);

}
