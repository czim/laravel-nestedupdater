<?php

namespace Czim\NestedModelUpdater\Contracts;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 */
interface NestedValidatorFactoryInterface
{
    /**
     * @param class-string<NestedValidatorInterface<TModel, TParent>> $class
     * @param array<int|string, mixed>                                $parameters constructor parameters for validator
     * @return NestedValidatorInterface<TModel, TParent>
     */
    public function make(string $class, array $parameters = []): NestedValidatorInterface;
}
