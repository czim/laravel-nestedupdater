<?php

namespace Czim\NestedModelUpdater\Contracts;

interface NestedValidatorFactoryInterface
{
    /**
     * @param class-string<NestedValidatorInterface> $class
     * @param array<int|string, mixed>               $parameters constructor parameters for validator
     * @return NestedValidatorInterface
     */
    public function make(string $class, array $parameters = []): NestedValidatorInterface;
}
