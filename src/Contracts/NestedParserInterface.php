<?php

namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 */
interface NestedParserInterface
{
    /**
     * @param class-string<TModel>                         $modelClass       FQN for model
     * @param null|string                                  $parentAttribute  the name of the attribute on the parent's data array
     * @param null|string                                  $nestedKey        dot-notation key for tree data (ex.: 'blog.comments.2.author')
     * @param null|TParent                                 $parentModel      the parent model, if this is a recursive/nested call
     * @param null|NestingConfigInterface<TParent, TModel> $config
     * @param null|class-string<TParent>                   $parentModelClass if the parentModel is not known, but its class is, set this
     */
    public function __construct(
        string $modelClass,
        ?string $parentAttribute = null,
        ?string $nestedKey = null,
        ?Model $parentModel = null,
        ?NestingConfigInterface $config = null,
        ?string $parentModelClass = null,
    );

    /**
     * Returns RelationInfo instance for nested data element by dot notation data key.
     *
     * @param string $key
     * @return RelationInfo<TModel>|false false if data could not be determined
     */
    public function getRelationInfoForDataKeyInDotNotation(string $key): RelationInfo|false;
}
