<?php
namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Czim\NestedModelUpdater\Data\UpdateResult;
use Czim\NestedModelUpdater\Exceptions\DisallowedNestedActionException;
use Czim\NestedModelUpdater\Exceptions\ModelSaveFailureException;
use Czim\NestedModelUpdater\Exceptions\NestedModelNotFoundException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
     * If available, the relation attribute on the parent model that may be used to
     * look up the nested config relation info.
     *
     * @var null|string
     */
    protected $parentAttribute;

    /**
     * The information about the relation on the parent's attribute, based on
     * parentModel & parentAttribute. Only set if not top-level.
     *
     * @var null|RelationInfo
     */
    protected $parentRelationInfo;

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
     * Whether any belongs to relations were updated so far
     *
     * @var bool
     */
    protected $belongsTosWereUpdated = false;


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
    ) {
        if (null === $config) {
            /** @var NestingConfigInterface $config */
            $config = app(NestingConfigInterface::class);
        }

        $this->modelClass      = $modelClass;
        $this->parentAttribute = $parentAttribute;
        $this->nestedKey       = $nestedKey;
        $this->parentModel     = $parentModel;
        $this->config          = $config;

        if ($parentAttribute && $parentModel) {
            $this->parentRelationInfo = $this->config->getRelationInfo($parentAttribute, get_class($parentModel));
        }
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
     * @param mixed|Model $model    either an existing model or its ID
     * @param string    $attribute  lookup column, if not primary key, only if $model is int
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    public function update(array $data, $model, $attribute = null)
    {
        if ( ! ($model instanceof Model)) {
            $model = $this->getModelByLookupAtribute($model, $attribute);
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
        $this->relationsAnalyzed     = false;
        $this->belongsTosWereUpdated = false;

        $this->normalizeData();

        if ($this->shouldUseTransaction()) {

            $result = null;
            DB::transaction(function () use (&$result) {
                $result = $this->performCreateOrUpdateProcess();
            });

        } else {

            $result = $this->performCreateOrUpdateProcess();
        }

        return $result;
    }

    /**
     * Performs that actual create or update action processing; separated
     * so it may be performed in a database transaction;
     *
     * @return UpdateResult
     * @throws ModelSaveFailureException
     */
    protected function performCreateOrUpdateProcess()
    {
        $this->config->setParentModel($this->modelClass);
        $this->analyzeNestedRelationsData();

        $this->prepareModel();

        // handle relationships; some need to be handled before saving the
        // model, since the foreign keys are stored in it; others can only
        // be handled afterwards, since the main model's key is stored as
        // foreign in their records.

        $this->handleBelongsToRelations();

        $this->updatedAndPersistModel();

        $this->handleHasAndBelongsToManyRelations();

        return (new UpdateResult())->setModel($this->model);
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
    protected function updatedAndPersistModel()
    {
        $modelData = $this->getDirectModelData();

        // if we have nothing to update, skip it
        if ( ! $this->isCreating && empty($modelData) && ! $this->belongsTosWereUpdated) {
            return;
        }

        $this->model->fill($modelData);

        // if we're saving a separate, top-level or belongs to related model,
        // we can simply save it by itself; other models should be saved
        // on their parent's relation.

        if ($this->shouldSaveModelOnParentRelation()) {
            $result = $this->parentModel->{$this->parentRelationInfo->relationMethod()}()->save(
                $this->model
            );
        } else {
            $result = $this->model->save();
        }

        if ( ! $result) {
            throw (new ModelSaveFailureException(
                "Failed persisting instance of {$this->modelClass} on "
                . ($this->isCreating ? 'create' : 'update') . ' operation'
            ))->setNestedKey($this->nestedKey);
        }
    }

    /**
     * Returns whether the current model should be saved on the parent's relation method.
     *
     * @return bool
     */
    protected function shouldSaveModelOnParentRelation()
    {
        if ( ! $this->parentModel || ! $this->parentRelationInfo) return false;

        return ! $this->parentRelationInfo->isBelongsTo();
    }

    /**
     * Handles the relations that need to be updated/created before the main
     * model is. Returns an array with results keyed by attribute.
     */
    protected function handleBelongsToRelations()
    {
        foreach ($this->relationInfo as $attribute => $info) {
            if ( ! $info->isBelongsTo()) continue;

            $this->belongsTosWereUpdated = true;

            /** @var Model|null $formerlyAssociatedModel */
            $formerlyAssociatedModel = $this->model->{$info->relationMethod()}()->first();

            $result = $this->handleNestedSingleUpdateOrCreate(
                Arr::get($this->data, $attribute),
                $info,
                $attribute
            );

            $result = ($result instanceof UpdateResult)
                ?   $result->model()
                :   $result;

            // update model by associating or dissociating as necessary
            if (    $result instanceof Model
                ||  (false !== $result && null !== $result)
            ) {
                // if the model associated now is different from the one before, delete if we should
                if (    $info->isDeleteDetached()
                    &&  $formerlyAssociatedModel
                    &&  $formerlyAssociatedModel->getKey() !== $result->getKey()
                ) {
                    $this->deleteFormerlyRelatedModel($formerlyAssociatedModel, $info);
                }

                $this->model->{$info->relationMethod()}()->associate($result);
                continue;
            }

            if ($info->isDeleteDetached() && $formerlyAssociatedModel) {
                $this->deleteFormerlyRelatedModel($formerlyAssociatedModel, $info);
            }

            $this->model->{$info->relationMethod()}()->dissociate();
        }
    }

    /**
     * Handles the relations that should be updated only after the model
     * is persisted.
     */
    protected function handleHasAndBelongsToManyRelations()
    {
        foreach ($this->relationInfo as $attribute => $info) {
            if ($info->isBelongsTo()) continue;

            // collect keys for (newly) connected models
            $keys = [];


            if ($info->isSingular()) {

                $result = $this->handleNestedSingleUpdateOrCreate(
                    Arr::get($this->data, $attribute),
                    $info,
                    $attribute
                );

                if (    ! ($result instanceof UpdateResult)
                    ||  ! $result->model()
                    ||  ! $result->model()->getKey()
                ) {
                    continue;
                }

                $keys[] = $result->model()->getKey();

            } else {
                // plural: an array with updates or links by primary key for
                // the related records, and syncs the relation

                foreach (Arr::get($this->data, $attribute, []) as $index => $data) {

                    $result = $this->handleNestedSingleUpdateOrCreate($data, $info, $attribute, $index);

                    if (    ! ($result instanceof UpdateResult)
                        ||  ! $result->model()
                        ||  ! $result->model()->getKey()
                    ) {
                        continue;
                    }

                    $keys[] = $result->model()->getKey();
                }
            }

            
            // sync relation, detaching anything not specifically listed in the dataset
            // unless we shouldn't

            if (is_a($info->relationClass(), BelongsToMany::class, true)) {
                $this->syncKeysForBelongsToManyRelation($info, $keys);
            } else {
                $this->syncKeysForHasManyRelation($info, $keys);
            }
        }
    }

    /**
     * Synchronizes the keys for a BelongsToMany relation.
     *
     * @param RelationInfo $info
     * @param array        $keys
     */
    protected function syncKeysForBelongsToManyRelation(RelationInfo $info, array $keys)
    {
        // if we should delete detached models, gather the model ids to delete
        if ($info->isDeleteDetached()) {
            $deleteKeys = $this->model->{$info->relationMethod()}()
                ->pluck($info->model()->getTable() . '.' . $info->model()->getKeyName())
                ->toArray();

            $deleteKeys = array_diff($deleteKeys, $keys);
        }

        // detach by default (for belongs to many), unless configured otherwise
        $detaching = (null === $info->getDetachMissing()) ? true : $info->getDetachMissing();

        $this->model->{$info->relationMethod()}()->sync($keys, $detaching);

        // delete models now detached, if configured to
        if ($info->isDeleteDetached() && isset($deleteKeys) && count($deleteKeys)) {

            foreach ($deleteKeys as $key) {
                $model = $this->getModelByLookupAtribute($key, null, get_class($info->model()));
                if ( ! $model) continue;

                $this->deleteFormerlyRelatedModel($model, $info);
            }
        }
    }

    /**
     * Synchronizes the keys for a HasMany relation. This is a special case,
     * since the actual new relations should already be linked after the
     * update/create handling recursive call.
     *
     * @param RelationInfo $info
     * @param array        $keys
     */
    protected function syncKeysForHasManyRelation(RelationInfo $info, array $keys)
    {
        // the relations might be disconnected, but only if the key is nullable
        // if deletion is not configured, we should attempt setting the key to
        // null

        // if it is a has-one relation, has a different default for detaching
        $isNotHasOne = is_a($info->relationClass(), HasOne::class, true);

        // do not detach by default (for hasmany), unless configured otherwise
        $detaching = (null === $info->getDetachMissing()) ? $isNotHasOne : $info->getDetachMissing();

        if ( ! $detaching && ! $info->isDeleteDetached()) return;

    }


    /**
     * Deletes a model that was formerly related to this model.
     * This performs a check to see if the model is at least not being used
     * for the same type of relation by another model. Note that this is not
     * safe by any means -- use on your own risk.
     *
     * @param Model        $model
     * @param RelationInfo $info
     */
    protected function deleteFormerlyRelatedModel(Model $model, RelationInfo $info)
    {
        $class = $this->modelClass;

        // To see if we can safely delete the child model, attempt to find a different
        // model of the same type as the parent model, that has a relation to the child model.
        // If one exists, it's still in use and should not be deleted.

        /** @var Model $class */
        $inUse = $class::whereHas($info->relationMethod(),
            function ($query) use ($model, $info) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query->where($info->model()->getTable() . '.' . $info->model()->getKeyName(), $model->id);
            })
            ->where($this->model->getTable() . '.' . $this->model->getKeyName(), '!=', $this->model->id)
            ->count();


        if ($inUse) return;

        $model->delete();
    }

    /**
     * Handles a nested update, link or create for a single model, returning
     * the result.
     *
     * @param mixed        $data
     * @param RelationInfo $info
     * @param string       $attribute
     * @param null|int     $index       optional, for to-many list indexes to append after attribute
     * @return UpdateResult|false       false if no model available
     * @throws DisallowedNestedActionException
     */
    protected function handleNestedSingleUpdateOrCreate($data, RelationInfo $info, $attribute, $index = null)
    {
        // handle model before, use results to save foreign key on the model later
        $nestedKey = $this->appendNestedKey($attribute, $index);

        $data = $this->normalizeNestedSingularData(
            $data,
            $info->model()->getKeyName(),
            $nestedKey
        );

        $updateId = Arr::get($data, $info->model()->getKeyName());

        // if the key is present, but the data is empty, the relation should be dissociated
        if (empty($data)) {
            return $this->makeUpdateResult();
        }

        // if we're not allowed to perform creates or updates, only handle the link
        // -- and this is not possible, stop the process or make sure it is handled right
        if ( ! $info->isUpdateAllowed()) {

            // if we cannot create it, we cannot proceed
            if (empty($updateId)) {
                throw (new DisallowedNestedActionException("Not allowed to create new for link-only nested relation"))
                    ->setNestedKey($nestedKey);
            }

            // strip everything but the key, so it is treated as a link-only operation
            $data = [ $info->model()->getKeyName() => $updateId ];
        }

        // get the existing model, if we have an update ID, or null if no match exists
        if ( ! empty($updateId)) {
            $existingModel = $this->getModelByLookupAtribute(
                $updateId,
                $info->model()->getKeyName(),
                get_class($info->model()),
                $nestedKey,
                false
            );
        } else {
            $existingModel = null;
        }

        // if a model for a given 'updateId' does not exist yet, and the model's key is
        // not an incrementing key, this should be treated as an attempt to create a record
        $creatingWithKey = ( ! $info->model()->incrementing && ! empty($updateId) && ! $existingModel);

        // if this is a link-only operation, mark it
        $onlyLinking = (count($data) == 1 && ! empty($updateId) && ! $creatingWithKey);

        // if we are allowed to update, but only the key is provided, treat this as a link-only operation
        // throw an exception if we couldn't find the model
        if ( ! $info->isUpdateAllowed() || $onlyLinking) {
            if ( ! $existingModel) {
                throw (new NestedModelNotFoundException())
                    ->setModel(get_class($info->model()))
                    ->setNestedKey($nestedKey);
            }

            return $this->makeUpdateResult($existingModel);
        }

        // otherwise, create or update, depending on whether the primary key is present in the data
        // if it is a create operation, make sure we're allowed to
        if ((empty($updateId) || $creatingWithKey) && ! $info->isCreateAllowed()) {
            throw (new DisallowedNestedActionException("Not allowed to create new for update-only nested relation"))
                ->setNestedKey($nestedKey);
        }

        $updater = $this->makeModelUpdater($info->updater(), [
            get_class($info->model()),
            $attribute,
            $nestedKey,
            $this->model,
            $this->config
        ]);
        
        $updateResult = (empty($updateId) || $creatingWithKey)
            ?   $updater->create($data)
            :   $updater->update($data, $updateId, $info->model()->getKeyName());

        // if for some reason the update or create was not succesful or
        // did not return a model, dissociate the relationship
        if ( ! $updateResult->model()) {
            return $this->makeUpdateResult();
        }

        return $updateResult;
    }

    /**
     * Normalizes data for a singular relationship;
     * assuming validation has already been passed.
     *
     * @param mixed       $data
     * @param string      $keyAttribute
     * @param null|string $nestedKey        child nested key that the data is for
     * @return array
     */
    protected function normalizeNestedSingularData($data, $keyAttribute = 'id', $nestedKey = null)
    {
        // data may be a scalar, in which case it is assumed
        // to be the primary key

        if (null === $data) {
            return [];
        }

        if (is_scalar($data)) {
            return [ $keyAttribute => $data ];
        }

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if ( ! is_array($data)) {
            throw new UnexpectedValueException(
                "Nested data should be key (scalar) or array data"
                . ($nestedKey ? " (nesting: {$nestedKey})" : '')
            );
        }

        return $data;
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
        $attribute = null,
        $modelClass = null,
        $nestedKey = null,
        $exceptionIfNotFound = true
    ) {
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
     * @param array  $parameters    parameters for model updater constructor
     * @return ModelUpdaterInterface
     */
    protected function makeModelUpdater($class, array $parameters)
    {
        /** @var ModelUpdaterInterface $updater */
        $updater = App::make($class, $parameters);

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
     * @param string   $key
     * @param null|int $index
     * @return string
     */
    protected function appendNestedKey($key, $index = null)
    {
        return ($this->nestedKey ? $this->nestedKey . '.' : '')
             . $key
             . (null !== $index ? '.' . $index : '');
    }

    /**
     * Returns UpdateResult instance for standard precluded responses.
     *
     * @param Model $model
     * @param bool  $success
     * @return UpdateResult
     */
    protected function makeUpdateResult(Model $model = null, $success = true)
    {
        return (new UpdateResult())
            ->setModel($model)
            ->setSuccess($success);
    }

    /**
     * Returns whether the update/create should be performed in a transaction.
     *
     * @return boolean
     */
    protected function shouldUseTransaction()
    {
        if ($this->noDatabaseTransaction || ! Config::get('nestedmodelupdater.database-transactions')) {
            return false;
        }

        // if not explicitly disabled, transactions are used only for the top
        // level, so when no nested key has been set at all.
        return null === $this->nestedKey;
    }

    // ------------------------------------------------------------------------------
    //      Getters / Setters
    // ------------------------------------------------------------------------------

    /**
     * @return $this
     */
    public function enableDatabaseTransaction()
    {
        $this->noDatabaseTransaction = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableDatabaseTransaction()
    {
        $this->noDatabaseTransaction = true;

        return $this;
    }

}
