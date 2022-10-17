<?php

namespace Czim\NestedModelUpdater\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ModelUpdaterFactoryInterface
{
    /**
     * @param class-string<ModelUpdaterInterface> $class
     * @param array<int|string, mixed>            $parameters constructor parameters for model updater
     * @return ModelUpdaterInterface<Model, Model>
     */
    public function make(string $class, array $parameters = []): ModelUpdaterInterface;
}
