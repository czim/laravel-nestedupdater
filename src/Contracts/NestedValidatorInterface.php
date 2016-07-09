<?php
namespace Czim\NestedModelUpdater\Contracts;

use Illuminate\Database\Eloquent\Model;

interface NestedValidatorInterface extends NestedParserInterface
{

    /**
     * @param string                      $modelClass      FQN for model
     * @param null|string                 $parentAttribute the name of the attribute on the parent's data array
     * @param null|string                 $nestedKey       dot-notation key for tree data (ex.: 'blog.comments.2.author')
     * @param null|Model                  $parentModel     the parent model, if this is a recursive/nested call
     * @param null|NestingConfigInterface $config
     */
    public function __construct(
        $modelClass,
        $parentAttribute = null,
        $nestedKey = null,
        Model $parentModel = null,
        NestingConfigInterface $config = null
    );

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
