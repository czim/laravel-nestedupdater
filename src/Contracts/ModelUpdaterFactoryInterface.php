<?php

namespace Czim\NestedModelUpdater\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 */
interface ModelUpdaterFactoryInterface
{
    /**
     * @param class-string<ModelUpdaterInterface<TModel, TParent>> $class
     * @param array<int|string, mixed>                             $parameters constructor parameters for model updater
     * @return ModelUpdaterInterface<TModel, TParent>
     */
    public function make(string $class, array $parameters = []): ModelUpdaterInterface;
}
