<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Traits;

use Czim\NestedModelUpdater\Contracts\TemporaryIdsInterface;
use Czim\NestedModelUpdater\Contracts\TracksTemporaryIdsInterface;
use Czim\NestedModelUpdater\Exceptions\InvalidNestedDataException;
use Illuminate\Database\Eloquent\Model;

/**
 * @see TracksTemporaryIdsInterface
 */
trait TracksTemporaryIds
{
    /**
     * Information about temporary ids analyzed so far.
     *
     * @var null|TemporaryIdsInterface
     */
    protected ?TemporaryIdsInterface $temporaryIds = null;

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
     * @return $this
     */
    public function setTemporaryIds(TemporaryIdsInterface $ids): static
    {
        $this->temporaryIds = $ids;

        return $this;
    }

    public function getTemporaryIds(): ?TemporaryIdsInterface
    {
        return $this->temporaryIds;
    }

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
     * @return $this
     * @throws InvalidNestedDataException
     */
    protected function checkTemporaryIdsUsage(): static
    {
        foreach ($this->temporaryIds->getKeys() as $key) {
            if ($this->temporaryIds->getDataForId($key) === null) {
                throw new InvalidNestedDataException("No create data defined for temporary ID '{$key}'");
            }

            if (! $this->temporaryIds->isAllowedToCreateForId($key)) {
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
     * @param string               $key
     * @param array<string, mixed> $data
     * @return $this
     * @throws InvalidNestedDataException
     */
    protected function checkDataAttributeKeysForTemporaryId(string $key, array $data): static
    {
        $modelClass = $this->temporaryIds->getModelClassForId($key);

        /** @var Model $model */
        $model = new $modelClass();

        // If the key is in the creating data, and it is an incrementing key, there is a mixup,
        // the data should not be for an update.
        if ($model->incrementing && array_key_exists($model->getKeyName(), $data)) {
            throw new InvalidNestedDataException(
                "Create data defined for temporary ID '{$key}' must not contain primary key value."
            );
        }

        // If data is already set for the temporary ID, it should be exactly the same.
        $setData = $this->temporaryIds->getDataForId($key);
        if ($setData !== null && $data !== $setData) {
            throw new InvalidNestedDataException(
                "Multiple inconsistent create data definitions given for temporary ID '{$key}'."
            );
        }

        return $this;
    }
}
