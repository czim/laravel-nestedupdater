<?php
namespace Czim\NestedModelUpdater\Contracts;

use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\ModelSaveFailureException;
use Illuminate\Database\Eloquent\Model;

interface ModelUpdaterInterface
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
     * Creates a new model with (potential) nested data
     *
     * @param array $data
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function create(array $data);

    /**
     * Updates an existing model with (potential) nested update data
     *
     * @param array     $data
     * @param int|Model $model      either an existing model or its ID
     * @param string    $attribute  lookup column, if not primary key, only if $model is int
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function update(array $data, $model, $attribute = null);

}
