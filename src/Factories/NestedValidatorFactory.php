<?php
namespace Czim\NestedModelUpdater\Factories;

use Illuminate\Support\Facades\App;
use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Czim\NestedModelUpdater\NestedValidator;
use ReflectionClass;
use UnexpectedValueException;

class NestedValidatorFactory implements NestedValidatorFactoryInterface
{

    /**
     * Makes a nested model validator instance.
     *
     * @param string $class
     * @param array  $parameters    constructor parameters for validator
     * @return NestedValidatorInterface
     */
    public function make($class, array $parameters = []): NestedValidatorInterface
    {
        if ($class === NestedValidatorInterface::class) {
            $class = $this->getDefaultValidatorClass();
        }

        if ( ! count($parameters)) {

            $validator = App::make($class);

        } else {

            try {
                $reflectionClass = new ReflectionClass($class);
                $validator = $reflectionClass->newInstanceArgs($parameters);

            } catch (\Exception $e) {

                $validator = $e->getMessage();
            }
        }

        if ( ! $validator) {
            throw new UnexpectedValueException(
                "Expected NestedValidatorInterface instance, got nothing for '{$class}'"
            );
        }

        if ( ! ($validator instanceof NestedValidatorInterface)) {
            throw new UnexpectedValueException(
                'Expected NestedValidatorInterface instance, got ' . get_class($class) . ' instead'
            );
        }

        return $validator;
    }

    /**
     * Returns the default class to use, if the interface is given as a class.
     *
     * @return string
     */
    protected function getDefaultValidatorClass(): string
    {
        return NestedValidator::class;
    }

}
