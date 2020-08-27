<?php

namespace Czim\NestedModelUpdater\Traits;

use Czim\NestedModelUpdater\Contracts\TemporaryIdsInterface;
use Czim\NestedModelUpdater\Contracts\TracksTemporaryIdsInterface;
use Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException;

trait TracksTemporaryIds
{
    /**
     * Information about temporary ids so far analyzed
     *
     * @var null|TemporaryIdsInterface
     */
    protected $temporaryIds;

    /**
     * Returns whether temporary ID handling is enabled.
     *
     * @return boolean
     */
    public function isHandlingTemporaryIds(): bool
    {
        return (bool) config('nestedmodelupdater.allow-temporary-ids');
    }

    /**
     * Sets the container for tracking temporary IDs.
     *
     * @param TemporaryIdsInterface $ids
     * @return $this|TracksTemporaryIdsInterface
     */
    public function setTemporaryIds(TemporaryIdsInterface $ids): TracksTemporaryIdsInterface
    {
        $this->temporaryIds = $ids;

        return $this;
    }

    /**
     * @return TemporaryIdsInterface|null
     */
    public function getTemporaryIds(): ?TemporaryIdsInterface
    {
        return $this->temporaryIds;
    }

    /**
     * Returns whether temporary ID container has been defined.
     *
     * @return bool
     */
    protected function hasTemporaryIds(): bool
    {
        return null !== $this->temporaryIds;
    }

    /**
     * Returns the attribute key for the temporary ID in nested data sets.
     *
     * @return string
     */
    protected function getTemporaryIdAttributeKey(): string
    {
        return config('nestedmodelupdater.temporary-id-key');
    }

    /**
     * Checks whether all the temporary ids are correctly set and
     * all of them have data that can be used.
     *
     * @return $this|TracksTemporaryIdsInterface
     * @throws InvalidNestedDataException
     */
    protected function checkTemporaryIdsUsage(): TracksTemporaryIdsInterface
    {
        foreach ($this->temporaryIds->getKeys() as $key) {

            if (null === $this->temporaryIds->getDataForId($key)) {
                throw new InvalidNestedDataException("No create data defined for temporary ID '{$key}'");
            }

            if ( ! $this->temporaryIds->isAllowedToCreateForId($key)) {
                throw new InvalidNestedDataException(
                    "Not allowed to create new model for temporary ID '{$key}' for any referenced nested relation"
                );
            }
        }

        return $this;
    }

    /**
     * Checks whether the attribute keys for the create data of a given temporary ID key.
     *
     * @param string $key
     * @param array  $data
     * @return $this|TracksTemporaryIdsInterface
     * @throws InvalidNestedDataException
     */
    protected function checkDataAttributeKeysForTemporaryId(string $key, array $data): TracksTemporaryIdsInterface
    {
        $modelClass = $this->temporaryIds->getModelClassForId($key);
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelClass;

        // if the key is in the creating data, and it is an incrementing key,
        // there is a mixup, the data should not be for an update
        if ($model->incrementing && array_key_exists($model->getKeyName(), $data)) {
            throw new InvalidNestedDataException(
                "Create data defined for temporary ID '{$key}' must not contain primary key value."
            );
        }

        // if data is already set for the temporary ID, it should be exactly the same
        $setData = $this->temporaryIds->getDataForId($key);
        if (null !== $setData && $data !== $setData) {
            throw new InvalidNestedDataException(
                "Multiple inconsistent create data definitions given for temporary ID '{$key}'."
            );
        }

        return $this;
    }
}
