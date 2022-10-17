<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\NestedParserInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException;
use Czim\NestedModelUpdater\Traits\TracksTemporaryIds;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Arr;
use UnexpectedValueException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TParent of \Illuminate\Database\Eloquent\Model
 *
 * @implements NestedParserInterface<TModel, TParent>
 */
abstract class AbstractNestedParser implements NestedParserInterface
{
    use TracksTemporaryIds;

    protected NestingConfigInterface $config;

    /**
     * The FQN for the main model being created or updated
     *
     * @var class-string<TModel>
     */
    protected string $modelClass;

    /**
     * Model being updated or created.
     *
     * @var TModel|null
     */
    protected ?Model $model = null;

    /**
     * If available the FQN of the parent model (may be set while parentModel instance is not).
     *
     * @var class-string<TParent>|null
     */
    protected ?string $parentModelClass;

    /**
     * If available, the (future) parent model of this record.
     *
     * @var TParent|null
     */
    protected ?Model $parentModel = null;

    /**
     * If available, the relation attribute on the parent model that may be used to
     * look up the nested config relation info.
     *
     * @var string|null
     */
    protected ?string $parentAttribute;

    /**
     * Dot-notation key, if relevant, representing the record currently updated or created.
     *
     * @var string|null
     */
    protected ?string $nestedKey;

    /**
     * Information about the nested relationships. If a key in the data array is present as a key in this array,
     * it should be considered a nested relation's data.
     *
     * @var array<string, RelationInfo<TParent>> keyed by nested attribute data key
     */
    protected array $relationInfo = [];

    /**
     * The information about the relation on the parent's attribute, based on parentModel & parentAttribute.
     * Only set if not top-level.
     *
     * @var RelationInfo<TModel>|null
     */
    protected ?RelationInfo $parentRelationInfo = null;

    /**
     * Whether the relations in the data array have been analyzed.
     *
     * @var bool
     */
    protected bool $relationsAnalyzed = false;

    /**
     * Data passed in for the create or update process.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];


    /**
     * @param class-string<TModel>                         $modelClass       FQN for model
     * @param string|null                                  $parentAttribute  the name of the attribute on the parent's data array
     * @param string|null                                  $nestedKey        dot-notation key for tree data (ex.: 'blog.comments.2.author')
     * @param TParent|null                                 $parentModel      the parent model, if this is a recursive/nested call
     * @param NestingConfigInterface<TParent, TModel>|null $config
     * @param class-string<TParent>|null                   $parentModelClass if the parentModel is not known, but its class is, set this
     */
    public function __construct(
        string $modelClass,
        ?string $parentAttribute = null,
        ?string $nestedKey = null,
        Model $parentModel = null,
        NestingConfigInterface $config = null,
        ?string $parentModelClass = null
    ) {
        if ($config === null) {
            /** @var NestingConfigInterface $config */
            $config = app(NestingConfigInterface::class);
        }

        $this->modelClass       = $modelClass;
        $this->parentAttribute  = $parentAttribute;
        $this->nestedKey        = $nestedKey;
        $this->parentModel      = $parentModel;
        $this->config           = $config;
        $this->parentModelClass = $parentModel ? $parentModel::class : $parentModelClass;

        if ($parentAttribute && $this->parentModelClass) {
            $this->parentRelationInfo = $this->config->getRelationInfo($parentAttribute, $this->parentModelClass);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRelationInfoForDataKeyInDotNotation(string $key): RelationInfo|false
    {
        $explodedKeys   = explode('.', $key);
        $nextLevelKey   = array_shift($explodedKeys);
        $nextLevelIndex = null;

        if (count($explodedKeys) && is_numeric(head($explodedKeys))) {
            $nextLevelIndex = (int) array_shift($explodedKeys);
        }

        $remainingKey = implode('.', $explodedKeys);

        // prepare the next recursive step and pass on the key
        // get the info for the next key, make sure that the info is loaded

        /** @var RelationInfo<TModel> $info */
        $info = Arr::get($this->relationInfo, $nextLevelKey);

        if (! $info) {
            $info = $this->getRelationInfoForKey($nextLevelKey);
        }

        if (! $info) {
            return false;
        }

        // we only need the updater if we cannot derive the model
        // class directly from the relation info.
        if (empty($remainingKey)) {
            return $info;
        }

        $updater = $this->makeNestedParser($info->updater(), [
            get_class($info->model()),
            $nextLevelKey,
            $this->appendNestedKey($nextLevelKey, $nextLevelIndex),
            $this->model,
            $this->config,
        ]);

        return $updater->getRelationInfoForDataKeyInDotNotation($remainingKey);
    }

    /**
     * Analyzes data to find nested relations data, and stores information about each.
     */
    protected function analyzeNestedRelationsData(): void
    {
        $this->relationInfo = [];

        foreach ($this->data as $key => $value) {
            if (! $this->config->isKeyNestedRelation($key)) {
                continue;
            }

            $this->relationInfo[ $key ] = $this->getRelationInfoForKey($key);
        }

        $this->relationsAnalyzed = true;
    }

    /**
     * Returns data array containing only the data that should be stored on the main model being updated/created.
     *
     * @return array<string, mixed>
     */
    protected function getDirectModelData(): array
    {
        // this only works if the relations have been analyzed
        if (! $this->relationsAnalyzed) {
            $this->analyzeNestedRelationsData();
        }

        return Arr::except($this->data, array_keys($this->relationInfo));
    }

    /**
     * Makes a nested model parser or updater instance, for recursive use.
     *
     * @param class-string<NestedParserInterface> $class      FQN of updater
     * @param array<int, mixed>                   $parameters parameters for model updater constructor
     * @return NestedParserInterface<Model, TModel>
     */
    abstract protected function makeNestedParser(string $class, array $parameters): NestedParserInterface;

    /**
     * Returns nested key for the current full-depth nesting.
     *
     * @param string          $key
     * @param null|string|int $index
     * @return string
     */
    protected function appendNestedKey(string $key, int|string|null $index = null): string
    {
        return ($this->nestedKey ? $this->nestedKey . '.' : '')
            . $key
            . ($index !== null ? '.' . $index : '');
    }

    /**
     * Returns and stores relation info for a given nested model key.
     *
     * @param string $key
     * @return RelationInfo<Model>
     */
    protected function getRelationInfoForKey(string $key): RelationInfo
    {
        $this->relationInfo[ $key ] = $this->config->getRelationInfo($key, $this->modelClass);

        return $this->relationInfo[ $key ];
    }

    /**
     * Returns whether this instance is performing a top-level operation,
     * as opposed to a nested at any recursion depth below it.
     *
     * @return bool
     */
    protected function isTopLevel(): bool
    {
        return $this->parentAttribute === null
            && $this->parentRelationInfo === null;
    }

    /**
     * @param mixed                    $id         primary model key or lookup value
     * @param null|string              $attribute  primary model key name or lookup column, if null, uses find() method
     * @param null|class-string<Model> $modelClass optional, if not looking up the main model
     * @param null|string              $nestedKey  optional, if not looking up the main model
     * @param bool                     $exceptionIfNotFound
     * @param bool                     $withTrashed
     * @return Model|null
     */
    protected function getModelByLookupAttribute(
        mixed $id,
        ?string $attribute = null,
        ?string $modelClass = null,
        ?string $nestedKey = null,
        bool $exceptionIfNotFound = true,
        bool $withTrashed = false,
    ): ?Model {
        $class     = $modelClass ?: $this->modelClass;
        $model     = new $class();
        $nestedKey = $nestedKey ?: $this->nestedKey;

        if (! $model instanceof Model) {
            throw new UnexpectedValueException("Model class FQN expected, got {$class} instead.");
        }

        /** @var Builder $queryBuilder */
        $queryBuilder = $model::query();

        if ($withTrashed && $queryBuilder->hasMacro('withTrashed')) {
            $queryBuilder->withoutGlobalScope(SoftDeletingScope::class);
        }

        /** @var Model $model */
        $model = $queryBuilder->where($attribute ?? $model->getKeyName(), $id)->first();

        if (! $model && $exceptionIfNotFound) {
            throw (new NestedModelNotFoundException())
                ->setModel($class)
                ->setNestedKey($nestedKey);
        }

        return $model;
    }

    /**
     * @param mixed                    $id         primary model key or lookup value
     * @param null|string              $attribute  primary model key name or lookup column, if null, uses find() method
     * @param null|class-string<Model> $modelClass optional, if not looking up the main model
     * @return bool
     */
    protected function checkModelExistsByLookupAtribute(
        mixed $id,
        ?string $attribute = null,
        ?string $modelClass = null
    ): bool {
        $class = $modelClass ?: $this->modelClass;
        $model = new $class();

        if (! $model instanceof Model) {
            throw new UnexpectedValueException("Model class FQN expected, got {$class} instead.");
        }

        if ($attribute === null) {
            return null !== $model->query()->find($id);
        }

        return $model->query()->where($attribute, $id)->count() > 0;
    }
}
