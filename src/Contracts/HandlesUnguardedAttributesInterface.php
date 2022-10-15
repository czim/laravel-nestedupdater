<?php

namespace Czim\NestedModelUpdater\Contracts;

interface HandlesUnguardedAttributesInterface
{
    /**
     * Returns list of currently queued unguarded attributes.
     *
     * @return array<string, mixed>
     */
    public function getUnguardedAttributes(): array;

    /**
     * Sets a list of unguarded attributes to store directly on the model, bypassing the fillable guard.
     *
     * @param array<string, mixed> $attributes associative key value pairs
     * @return $this
     */
    public function setUnguardedAttributes(array $attributes): static;

    /**
     * Sets an unguarded attribute to store directly on the model, bypassing the fillable guard.
     *
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function setUnguardedAttribute(string $key, mixed $value): static;

    /**
     * Clears list of currently to be applied unguarded attributes.
     *
     * @return $this
     */
    public function clearUnguardedAttributes(): static;
}
