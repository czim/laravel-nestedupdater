<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Factories;

use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Czim\NestedModelUpdater\NestedValidator;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Throwable;
use UnexpectedValueException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @implements NestedValidatorFactoryInterface<TModel, TParent>
 */
class NestedValidatorFactory implements NestedValidatorFactoryInterface
{
    /**
     * @param class-string<NestedValidatorInterface<TModel, TParent>> $class
     * @param array<int|string, mixed>                                $parameters constructor parameters for validator
     * @return NestedValidatorInterface<Model, Model>
     */
    public function make(string $class, array $parameters = []): NestedValidatorInterface
    {
        if ($class === NestedValidatorInterface::class) {
            $class = $this->getDefaultValidatorClass();
        }

        if (! count($parameters)) {
            $validator = app($class);
        } else {
            try {
                /** @var ReflectionClass<NestedValidatorInterface<Model, Model>> $reflectionClass */
                $reflectionClass = new ReflectionClass($class);
                $validator       = $reflectionClass->newInstanceArgs($parameters);
            } catch (Throwable $exception) {
                $validator = $exception->getMessage();
            }
        }

        if (! $validator) {
            throw new UnexpectedValueException(
                "Expected NestedValidatorInterface instance, got nothing for '{$class}'"
            );
        }

        if (! $validator instanceof NestedValidatorInterface) {
            throw new UnexpectedValueException(
                'Expected NestedValidatorInterface instance, got ' . get_class($validator) . ' instead'
            );
        }

        return $validator;
    }

    /**
     * Returns the default class to use, if the interface is given as a class.
     *
     * @return class-string<NestedValidatorInterface>
     */
    protected function getDefaultValidatorClass(): string
    {
        return NestedValidator::class;
    }
}
