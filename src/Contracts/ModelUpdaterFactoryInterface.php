<?php
namespace Czim\NestedModelUpdater\Contracts;

interface ModelUpdaterFactoryInterface
{

    /**
     * Makes a model updater instance.
     *
     * @param string $class
     * @param array  $parameters    constructor parameters for model updater
     * @return ModelUpdaterInterface
     */
    public function make(string $class, array $parameters = []): ModelUpdaterInterface;

}
