<?php
namespace Czim\NestedModelUpdater\Contracts;

interface NestedValidatorInterface extends NestedParserInterface
{

    /**
     * Performs validation and returns whether it succeeds.
     *
     * @param array $data
     * @param bool  $creating   if false, validate for update
     * @return bool
     */
    public function validate(array $data, $creating = true);

    /**
     * Returns validation rules array for full nested data.
     *
     * @param array $data
     * @param bool  $creating
     * @return array
     */
    public function validationRules(array $data, $creating = true);

    /**
     * Returns validation messages, if validation has been performed.
     *
     * @return null|\Illuminate\Contracts\Support\MessageBag
     */
    public function messages();

    /**
     * Returns validation rules for the current model only
     *
     * @param bool $prefixNesting   if true, prefixes the validation rules with the relevant key nesting.
     * @param bool $creating
     * @return array
     */
    public function getDirectModelValidationRules($prefixNesting = false, $creating = true);

}
