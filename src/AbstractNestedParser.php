<?php
namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\NestedParserInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException;
use Czim\NestedModelUpdater\Traits\TracksTemporaryIds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use UnexpectedValueException;

abstract class AbstractNestedParser implements NestedParserInterface
{
    use TracksTemporaryIds;

    /**
     * @var NestingConfigInterface
     */
    protected $config;

    /**
     * The FQN for the main model being created or updated
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Model being updated or created
     *
     * @var null|Model
     */
    protected $model;

    /**
     * If available, the (future) parent model of this record
     *
     * @var null|Model
     */
    protected $parentModel;

    /**
     * If available the FQN of the parent model (may be set while parentModel instance is not)
     *
     * @var null|string
     */
    protected $parentModelClass;

    /**
     * If available, the relation attribute on the parent model that may be used to
     * look up the nested config relation info.
     *
     * @var null|string
     */
    protected $parentAttribute;

    /**
     * Dot-notation key, if relevant, representing the record currently updated or created
     *
     * @var null|string
     */
    protected $nestedKey;
    /**
     * Information about the nested relationships. If a key in the data array
     * is present as a key in this array, it should be considered a nested
     * relation's data.
     *
     * @var RelationInfo[]  keyed by nested attribute data key
     */
    protected $relationInfo;

    /**
     * The information about the relation on the parent's attribute, based on
     * parentModel & parentAttribute. Only set if not top-level.
     *
     * @var null|RelationInfo
     */
    protected $parentRelationInfo;

    /**
     * Whether the relations in the data array have been analyzed
     *
     * @var bool
     */
    protected $relationsAnalyzed = false;

    /**
     * Data passed in for the create or update process
     *
     * @var array
     */
    protected $data;


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
        Model $parentModel = null,
        NestingConfigInterface $config = null,
        ?string $parentModelClass = null
    ) {
        if (null === $config) {
            /** @var NestingConfigInterface $config */
            $config = App::make(NestingConfigInterface::class);
        }

        $this->modelClass       = $modelClass;
        $this->parentAttribute  = $parentAttribute;
        $this->nestedKey        = $nestedKey;
        $this->parentModel      = $parentModel;
        $this->config           = $config;
        $this->parentModelClass = $parentModel ? get_class($parentModel) : $parentModelClass;

        if ($parentAttribute && $this->parentModelClass) {

            $this->parentRelationInfo = $this->config->getRelationInfo($parentAttribute, $this->parentModelClass);
        }
    }

    /**
     * Returns RelationInfo instance for nested data element by dot notation data key.
     *
     * @param string $key
     * @return RelationInfo|false     false if data could not be determined
     */
    public function getRelationInfoForDataKeyInDotNotation($key)
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

        /** @var RelationInfo $info */
        if ( ! ($info = Arr::get($this->relationInfo, $nextLevelKey))) {
            $info = $this->getRelationInfoForKey($nextLevelKey);
        }

        if ( ! $info) {
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
            $this->config
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

            if ( ! $this->config->isKeyNestedRelation($key)) {
                continue;
            }

            $this->relationInfo[$key] = $this->getRelationInfoForKey($key);
        }

        $this->relationsAnalyzed = true;
    }

    /**
     * Returns data array containing only the data that should be stored
     * on the main model being updated/created.
     *
     * @return array
     */
    protected function getDirectModelData(): array
    {
        // this only works if the relations have been analyzed
        if ( ! $this->relationsAnalyzed) {
            $this->analyzeNestedRelationsData();
        }

        return Arr::except($this->data, array_keys($this->relationInfo));
    }

    /**
     * Makes a nested model parser or updater instance, for recursive use.
     *
     * @param string $class         FQN of updater
     * @param array  $parameters    parameters for model updater constructor
     * @return NestedParserInterface
     */
    abstract protected function makeNestedParser(string $class, array $parameters): NestedParserInterface;

    /**
     * Returns nested key for the current full-depth nesting.
     *
     * @param string          $key
     * @param null|string|int $index
     * @return string
     */
    protected function appendNestedKey(string $key, $index = null): string
    {
        return ($this->nestedKey ? $this->nestedKey . '.' : '')
             . $key
             . (null !== $index ? '.' . $index : '');
    }

    /**
     * Returns and stores relation info for a given nested model key.
     *
     * @param string $key
     * @return RelationInfo
     */
    protected function getRelationInfoForKey(string $key): RelationInfo
    {
        $this->relationInfo[$key] = $this->config->getRelationInfo($key, $this->modelClass);

        return $this->relationInfo[$key];
    }

    /**
     * Returns whether this instance is performing a top-level operation,
     * as opposed to a nested at any recursion depth below it.
     *
     * @return boolean
     */
    protected function isTopLevel(): bool
    {
        return null === $this->parentAttribute && null === $this->parentRelationInfo;
    }

    /**
     * @param mixed       $id         primary model key or lookup value
     * @param null|string $attribute  primary model key name or lookup column, if null, uses find() method
     * @param null|string $modelClass optional, if not looking up the main model
     * @param null|string $nestedKey  optional, if not looking up the main model
     * @param bool        $exceptionIfNotFound
     * @return Model|null
     * @throws NestedModelNotFoundException
     */
    protected function getModelByLookupAtribute(
        $id,
        ?string $attribute = null,
        ?string $modelClass = null,
        ?string $nestedKey = null,
        bool $exceptionIfNotFound = true
    ): ?Model {

        $class     = $modelClass ?: $this->modelClass;
        $model     = new $class;
        $nestedKey = $nestedKey ?: $this->nestedKey;

        if ( ! ($model instanceof Model)) {
            throw new UnexpectedValueException("Model class FQN expected, got {$class} instead.");
        }

        /** @var Model $model */
        if (null === $attribute) {
            $model = $model::find($id);
        } else {
            $model = $model::where($attribute, $id)->first();
        }

        if ( ! $model && $exceptionIfNotFound) {
            throw (new NestedModelNotFoundException())
                ->setModel($class)
                ->setNestedKey($nestedKey);
        }

        return $model;
    }

    /**
     * @param mixed       $id         primary model key or lookup value
     * @param null|string $attribute  primary model key name or lookup column, if null, uses find() method
     * @param null|string $modelClass optional, if not looking up the main model
     * @return bool
     */
    protected function checkModelExistsByLookupAtribute(
        $id,
        ?string $attribute = null,
        ?string $modelClass = null
    ): bool {

        $class = $modelClass ?: $this->modelClass;
        $model = new $class;

        if ( ! ($model instanceof Model)) {
            throw new UnexpectedValueException("Model class FQN expected, got {$class} instead.");
        }

        /** @var Model $model */
        if (null === $attribute) {
            return null !== $model::find($id);
        }

        return $model::where($attribute, $id)->count() > 0;
    }

}
