<?php

namespace Czim\NestedModelUpdater\Contracts;

interface ModelUpdaterFactoryInterface
{
    /**
     * Makes a model updater instance.
     *
     * @param class-string<ModelUpdaterInterface> $class
     * @param array<int|string, mixed>            $parameters constructor parameters for model updater
     * @return ModelUpdaterInterface
     */
    public function make(string $class, array $parameters = []): ModelUpdaterInterface;
}
