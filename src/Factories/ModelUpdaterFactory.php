<?php
namespace Czim\NestedModelUpdater\Factories;

use App;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterFactoryInterface;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\ModelUpdater;
use ReflectionClass;
use UnexpectedValueException;

class ModelUpdaterFactory implements ModelUpdaterFactoryInterface
{

    /**
     * Makes a model updater instance.
     *
     * @param string $class
     * @param array  $parameters    constructor parameters for model updater
     * @return ModelUpdaterInterface
     */
    public function make($class, array $parameters = []): ModelUpdaterInterface
    {
        if ($class === ModelUpdaterInterface::class) {
            $class = $this->getDefaultUpdaterClass();
        }

        if ( ! count($parameters)) {

            $updater = App::make($class);

        } else {

            try {
                $reflectionClass = new ReflectionClass($class);
                $updater = $reflectionClass->newInstanceArgs($parameters);

            } catch (\Exception $e) {

                $updater = $e->getMessage();
            }
        }

        if ( ! $updater) {
            throw new UnexpectedValueException(
                "Expected ModelUpdaterInterface instance, got nothing for '{$class}'"
            );
        }

        if ( ! ($updater instanceof ModelUpdaterInterface)) {
            throw new UnexpectedValueException(
                'Expected ModelUpdaterInterface instance, got ' . get_class($class) . ' instead'
            );
        }

        return $updater;
    }

    /**
     * Returns the default class to use, if the interface is given as a class.
     *
     * @return string
     */
    protected function getDefaultUpdaterClass(): string
    {
        return ModelUpdater::class;
    }

}
