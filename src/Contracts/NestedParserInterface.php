<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Database\Eloquent\Model;

interface NestedParserInterface
{
    /**
     * @param string                      $modelClass       FQN for model
     * @param null|string                 $parentAttribute  the name of the attribute on the parent's data array
     * @param null|string                 $nestedKey        dot-notation key for tree data (ex.: 'blog.comments.2.author')
     * @param null|Model                  $parentModel      the parent model, if this is a recursive/nested call
     * @param null|NestingConfigInterface $config
     * @param null|string                 $parentModelClass if the parentModel is not known, but its class is, set this
     */
    public function __construct(
        string $modelClass,
        ?string $parentAttribute = null,
        ?string $nestedKey = null,
        ?Model $parentModel = null,
        ?NestingConfigInterface $config = null,
        ?string $parentModelClass = null
    );

    /**
     * Returns RelationInfo instance for nested data element by dot notation data key.
     *
     * @param string $key
     * @return RelationInfo|false     false if data could not be determined
     */
    public function getRelationInfoForDataKeyInDotNotation($key);
}
