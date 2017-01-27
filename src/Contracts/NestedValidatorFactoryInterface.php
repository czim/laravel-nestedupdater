<?php
namespace Czim\NestedModelUpdater\Contracts;

interface NestedValidatorFactoryInterface
{

    /**
     * Makes a nested model validator instance.
     *
     * @param string $class
     * @param array  $parameters    constructor parameters for validator
     * @return NestedValidatorInterface
     */
    public function make($class, array $parameters = []);

}
