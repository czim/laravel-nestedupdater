<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Factories;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterFactoryInterface;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\ModelUpdater;
use ReflectionClass;
use Throwable;
use UnexpectedValueException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @implements ModelUpdaterFactoryInterface<TModel, TParent>
 */
class ModelUpdaterFactory implements ModelUpdaterFactoryInterface
{
    /**
     * @param class-string<ModelUpdaterInterface<TModel, TParent>> $class
     * @param array<int|string, mixed>                             $parameters constructor parameters for model updater
     * @return ModelUpdaterInterface<TModel, TParent>
     */
    public function make(string $class, array $parameters = []): ModelUpdaterInterface
    {
        if ($class === ModelUpdaterInterface::class) {
            $class = $this->getDefaultUpdaterClass();
        }

        if (! count($parameters)) {
            $updater = app($class);
        } else {
            try {
                /** @var ReflectionClass<ModelUpdaterInterface<TModel, TParent>> $reflectionClass */
                $reflectionClass = new ReflectionClass($class);
                $updater         = $reflectionClass->newInstanceArgs($parameters);
            } catch (Throwable $exception) {
                $updater = $exception->getMessage();
            }
        }

        if (! $updater) {
            throw new UnexpectedValueException(
                "Expected ModelUpdaterInterface instance, got nothing for '{$class}'"
            );
        }

        if (! $updater instanceof ModelUpdaterInterface) {
            throw new UnexpectedValueException(
                'Expected ModelUpdaterInterface instance, got ' . get_class($updater) . ' instead'
            );
        }

        return $updater;
    }

    /**
     * Returns the default class to use, if the interface is given as a class.
     *
     * @return class-string<ModelUpdaterInterface>
     */
    protected function getDefaultUpdaterClass(): string
    {
        return ModelUpdater::class;
    }
}
