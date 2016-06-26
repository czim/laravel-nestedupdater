<?php
namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\ModelSaveFailureException;
use Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException;
use DB;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use UnexpectedValueException;

class ModelUpdater implements ModelUpdaterInterface
{

    /**
     * @var NestingConfigInterface
     */
    protected $config;

    /**
     * Dot-notation key, if relevant, representing the record currently updated or created
     *
     * @var null|string
     */
    protected $nestedKey;

    /**
     * If available, the (future) parent model of this record
     *
     * @var null|Model
     */
    protected $parentModel;

    /**
     * Data passed in for the create or update process
     *
     * @var array
     */
    protected $data;

    /**
     * Model being updated or created
     * 
     * @var null|Model
     */
    protected $model;

    /**
     * Whether we're currently creating or just updating
     *
     * @var boolean
     */
    protected $isCreating;

    /**
     * The FQN for the main model being created or updated
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Normally, the whole update is performed in a database transaction, but only
     * on the top level. If this is set to true, no transactions are used.
     *
     * @var bool
     */
    protected $noDatabaseTransaction = false;

    /**
     * Information about the nested relationships. If a key in the data array
     * is present as a key in this array, it should be considered a nested
     * relation's data.
     *
     * @var RelationInfo[]  keyed by nested attribute data key
     */
    protected $relationInfo;

    /**
     * Whether the relations in the data array have been analyzed
     *
     * @var bool
     */
    protected $relationsAnalyzed = false;


    /**
     * @param string                      $modelClass   FQN for model
     * @param null|string                 $nestedKey    dot-notation key for tree data (ex.: 'blog.comments.2.author')
     * @param null|Model                  $parent       the parent model, if this is a recursive/nested call
     * @param null|NestingConfigInterface $config
     */
    public function __construct(
        $modelClass,
        $nestedKey = null,
        Model $parent = null,
        NestingConfigInterface $config = null
    ) {
        if (null === $config) {
            /** @var NestingConfigInterface $config */
            $config = app(NestingConfigInterface::class);
        }

        $this->modelClass  = $modelClass;
        $this->nestedKey   = $nestedKey;
        $this->parentModel = $parent;
        $this->config      = $config;
    }


    /**
     * Creates a new model with (potential) nested data
     *
     * @param array $data
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function create(array $data)
    {
        $this->isCreating = true;
        $this->data       = $data;
        $this->model      = null;

        return $this->createOrUpdate();
    }

    /**
     * Updates an existing model with (potential) nested update data
     *
     * @param array     $data
     * @param int|Model $model      either an existing model or its ID
     * @param string    $attribute  lookup column, if not primary key, only if $model is int
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function update(array $data, $model, $attribute = null)
    {
        if ( ! ($model instanceof Model)) {
            $model = $this->getModelByLookupAtribute( (int) $model, $attribute);
        }

        $this->isCreating = false;
        $this->data       = $data;
        $this->model      = $model;
        
        return $this->createOrUpdate();
    }

    /**
     * Performs the nested create or update action.
     * The data, model and circumstances should already be set at this point.
     *
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    protected function createOrUpdate()
    {
        $this->relationsAnalyzed = false;

        $this->normalizeData();

        $this->beginTransaction();

        $this->config->setParentModel($this->modelClass);
        $this->analyzeNestedRelationsData();

        $this->prepareModel();

        // handle relationships; some need to be handled before saving the
        // model, since the foreign keys are stored in it; others can only
        // be handled afterwards, since the main model's key is stored as
        // foreign in their records.

        $this->handleBelongsToRelations();

        $this->handleModelSaving();

        $this->handleHasRelations();

        $result = (new UpdateResult())->setModel($this->model);

        $this->commitTransaction();

        return $result;
    }

    /**
     * Performs any normalization on the create or update data
     * Customize this to adjust the data property before the nesting
     * analysis & processing is performed.
     */
    protected function normalizeData()
    {
    }

    /**
     * Analyzes data to find nested relations data, and stores information about each.
     */
    protected function analyzeNestedRelationsData()
    {
        $this->relationInfo = [];

        foreach ($this->data as $key => $value) {
            if ( ! $this->config->isKeyNestedRelation($key)) continue;

            $this->relationInfo[$key] = $this->config->getRelationInfo($key, $this->modelClass);
        }

        $this->relationsAnalyzed = true;
    }

    /**
     * Prepares model property so it is ready for belongsTo relation updates.
     * When updating, the model is already retrieved and considered prepared.
     */
    protected function prepareModel()
    {
        if ( ! $this->isCreating) return;

        $modelClass  = $this->modelClass;
        $this->model = new $modelClass;
    }

    /**
     * Handles creating or updating the main model.
     *
     * @throws ModelSaveFailureException
     */
    protected function handleModelSaving()
    {
        $modelData = $this->getDirectModelData();

        // if we have nothing to update, skip it
        if ( ! $this->isCreating && empty($modelData)) {
            return;
        }

        $this->model->fill($modelData);


        // todo: consider whether it will be useful to stop here optionally
        // and just make the model without persisting it. If so, we should return
        // the unpersisted model after comitted the other changes...


        if ( ! $this->model->save()) {
            $this->rollbackTransaction();

            throw new ModelSaveFailureException(
                "Failed persisting {$this->modelClass} instance ("
                . ($this->isCreating ? 'create' : 'update') . ")"
            );
        }
    }

    /**
     * Handles the relations that need to be updated/created before the main
     * model is. Returns an array with results keyed by attribute.
     */
    protected function handleBelongsToRelations()
    {
        $results = [];

        foreach ($this->relationInfo as $attribute => $info) {
            if ( ! $info->isBelongsTo()) continue;

            $result = $this->handleNestedSingleUpdateOrCreate(
                Arr::get($this->data, $attribute),
                $info,
                $this->appendNestedKey($attribute)
            );

            $results[$attribute] = ($result instanceof UpdateResult)
                ?   $result->model()
                :   $result;
        }

        // apply results to the model, if appliccable
        if ( ! count($results)) return;

        foreach ($results as $attribute => $result) {

            // associate or dissociate as necessary
        }
    }

    /**
     * Handles the relations that should be updated only after the model
     * is persisted.
     */
    protected function handleHasRelations()
    {
        foreach ($this->relationInfo as $attribute => $info) {
            if ($info->isBelongsTo()) continue;

            // may be singular or plural in this case
            if ($info->isSingular()) {
                $data = $this->normalizeNestedSingularData(Arr::get($this->data, $attribute));
                continue;
            }

            // plural: an array with updates or links by primary key for
            // the related records, and syncs the relation

            $keys = [];

            foreach (Arr::get($this->data, $attribute, []) as $index => $data) {

                $data = $this->normalizeNestedSingularData($data);


            }
        }
    }

    /**
     * Handles a nested update, link or create for a single model, returning
     * the result.
     *
     * @param mixed        $data
     * @param RelationInfo $info
     * @param string       $nestedKey
     * @return bool|UpdateResult|mixed false if no model available
     *                                  mixed/scalar if just linked to this primary key value
     */
    protected function handleNestedSingleUpdateOrCreate($data, RelationInfo $info, $nestedKey)
    {
        // handle model before, use results to save foreign key on the model later
        $data     = $this->normalizeNestedSingularData($data);
        $updater  = $this->makeModelUpdater($info->updater(), $info->model(), $nestedKey);
        $updateId = Arr::get($data, $info->modelPrimaryKey());

        // if the key is present, but the data is empty, the relation should be dissociated
        if (empty($data)) {
            return false;
        }

        // if we're not allowed to perform creates or updates, only handle the link
        if ( ! $info->isUpdateAllowed()) {
            return $updateId;
        }

        // otherwise, create or update, depending on whether the primary key is
        // present in the data
        $updateResult = (empty($updateId))
            ?   $updater->create($data)
            :   $updater->update($data, $updateId, $info->modelPrimaryKey());

        // if for some reason the update or create was not succesful or
        // did not return a model, dissociate the relationship
        if ( ! $updateResult->model()) {
            return false;
        }

        return $updateResult;
    }

    /**
     * Normalizes data for a singular relationship;
     * assuming validation has already been passed.
     *
     * @param mixed  $data
     * @param string $keyAttribute
     * @return array
     */
    protected function normalizeNestedSingularData($data, $keyAttribute = 'id')
    {
        // data may be a scalar, in which case it is assumed
        // to be the primary key

        if (is_scalar($data)) {
            return [ $keyAttribute => $data ];
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if ( ! is_array($data)) {
            throw new UnexpectedValueException("Nested data should be key (scalar) or array data");
        }

        return $data;
    }


    /**
     * @param int         $id
     * @param null|string $attribute
     * @return Model
     */
    protected function getModelByLookupAtribute($id, $attribute = null)
    {
        $class = $this->modelClass;
        $model = new $class;

        if ( ! ($model instanceof Model)) {
            throw new UnexpectedValueException("Model class FQN expected, got {$class} instead.");
        }

        /** @var Model $model */
        if (null === $attribute) {
            $model = $model::find($id);
        } else {
            $model = $model::where($attribute, $id)->first();
        }

        if ( ! $model) {
            throw (new NestedModelNotFoundException())
                ->setModel($class)
                ->setNestedKey($this->nestedKey);
        }

        return $model;
    }

    /**
     * Returns whether a key in the data array contains nested relation
     * data. If false, this means that it should be a (fillable) value on
     * the main model being created/updated.
     *
     * @param string $key
     * @return boolean
     */
    protected function isAttributeNestedData($key)
    {
        // this only works if the relations have been analyzed
        if ( ! $this->relationsAnalyzed) {
            $this->analyzeNestedRelationsData();
        }

        return array_key_exists($key, $this->relationInfo);
    }

    /**
     * Returns data array containing only the data that should be stored
     * on the main model being updated/created.
     * 
     * @return array
     */
    protected function getDirectModelData()
    {
        // this only works if the relations have been analyzed
        if ( ! $this->relationsAnalyzed) {
            $this->analyzeNestedRelationsData();
        }

        return Arr::except($this->data, array_keys($this->relationInfo));
    }

    /**
     * @param string $class         FQN of updater
     * @param string $modelClass    FQN of related model
     * @param string $nestedKey
     * @return ModelUpdaterInterface
     */
    protected function makeModelUpdater($class, $modelClass, $nestedKey)
    {
        /** @var ModelUpdaterInterface $updater */
        $updater = App::make(
            $class,
            [
                $modelClass,
                $nestedKey,
                $this->model
            ]
        );

        if ( ! ($updater instanceof ModelUpdaterInterface)) {
            throw new UnexpectedValueException(
                "Expected ModelUpdaterInterface instance, got " . get_class($class) . ' instead'
            );
        }

        return $updater;
    }

    /**
     * Returns nested key for the current full-depth nesting.
     *
     * @param string $key
     * @return string
     */
    protected function appendNestedKey($key)
    {
        return ($this->nestedKey ? $this->nestedKey . '.' : '')
             . $key;
    }

    // ------------------------------------------------------------------------------
    //      Database Transaction
    // ------------------------------------------------------------------------------

    protected function beginTransaction()
    {
        if ( ! $this->shouldUseTransaction()) return;

        DB::beginTransaction();
    }

    protected function rollbackTransaction()
    {
        if ( ! $this->shouldUseTransaction()) return;

        DB::rollBack();
    }

    protected function commitTransaction()
    {
        if ( ! $this->shouldUseTransaction()) return;

        DB::beginTransaction();
    }

    /**
     * Returns whether the update/create should be performed in a transaction.
     *
     * @return boolean
     */
    protected function shouldUseTransaction()
    {
        if ($this->noDatabaseTransaction) {
            return false;
        }

        // if not explicitly disabled, transactions are used only for the top
        // level, so when no nested key has been set at all.
        return null === $this->nestedKey;
    }

}
