<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Exceptions;

trait StoresNestedKeyTrait
{
    /**
     * Dot-notation nested key for the record in the data array.
     *
     * @var string
     */
    protected string $nestedKey;

    /**
     * Set the dot-notation nested key for the affected model.
     *
     * @param string|null $nestedKey
     * @return $this
     */
    public function setNestedKey(?string $nestedKey): static
    {
        $this->nestedKey = $nestedKey ?? '';

        if ($nestedKey) {
            $this->message .= " (nesting: {$nestedKey})";
        }

        return $this;
    }

    /**
     * Get the dot-notation nested key for the affected model.
     *
     * @return string
     */
    public function getNestedKey(): string
    {
        return $this->nestedKey;
    }
}
