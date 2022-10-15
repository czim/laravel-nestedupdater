<?php

namespace Czim\NestedModelUpdater\Contracts;

use Illuminate\Contracts\Support\MessageBag;

interface NestedValidatorInterface extends NestedParserInterface
{
    /**
     * Performs validation and returns whether it succeeds.
     *
     * @param array<string, mixed> $data
     * @param bool                 $creating if false, validate for update
     * @return bool
     */
    public function validate(array $data, bool $creating = true): bool;

    /**
     * Returns validation rules array for full nested data.
     *
     * @param array<string, mixed> $data
     * @param bool                 $creating
     * @return array<string, mixed>
     */
    public function validationRules(array $data, bool $creating = true): array;

    /**
     * Returns validation messages, if validation has been performed.
     *
     * @return null|MessageBag
     */
    public function messages(): ?MessageBag;

    /**
     * Returns validation rules for the current model only.
     *
     * @param bool $prefixNesting if true, prefixes the validation rules with the relevant key nesting.
     * @param bool $creating
     * @return array<string, mixed>
     */
    public function getDirectModelValidationRules(bool $prefixNesting = false, bool $creating = true): array;
}
