<?php

namespace Czim\NestedModelUpdater\Contracts;

interface NestedValidatorFactoryInterface
{
    /**
     * Makes a nested model validator instance.
     *
     * @param class-string<NestedValidatorInterface> $class
     * @param array<string, mixed>                   $parameters constructor parameters for validator
     * @return NestedValidatorInterface
     */
    public function make(string $class, array $parameters = []): NestedValidatorInterface;
}
