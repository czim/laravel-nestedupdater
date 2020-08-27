<?php

namespace Czim\NestedModelUpdater\Traits;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterFactoryInterface;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;

/**
 * @mixin \Eloquent
 */
trait NestedUpdatable
{
    /**
     * {@inheritdoc}
     */
    public static function create(array $attributes = [])
    {
        /** @var NestedUpdatable|\Illuminate\Database\Eloquent\Model $this */
        $model = new static;

        /** @var ModelUpdaterInterface $updater */
        $updater = $model->getModelUpdaterInstance();

        $result = $updater->create($attributes);

        return $result->model();
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $attributes = [], array $options = [])
    {
        /** @var NestedUpdatable|\Illuminate\Database\Eloquent\Model $this */
        if ( ! $this->exists) {
            return false;
        }

        $updater = $this->getModelUpdaterInstance();

        $result = $updater->update($attributes, $this, null, $options);

        return $result->success();
    }

    /**
     * Makes an instance of the ModelUpdater.
     *
     * @return ModelUpdaterInterface
     */
    protected function getModelUpdaterInstance(): ModelUpdaterInterface
    {
        $class = (property_exists($this, 'modelUpdaterClass'))
            ?   $this->modelUpdaterClass
            :   ModelUpdaterInterface::class;

        $config = (property_exists($this, 'modelUpdaterConfigClass'))
            ?   app($this->modelUpdaterConfigClass)
            :   null;

        return $this->getModelUpdaterFactory()->make($class, [ get_class($this), null, null, null, $config ]);
    }

    /**
     * @return ModelUpdaterFactoryInterface
     */
    protected function getModelUpdaterFactory(): ModelUpdaterFactoryInterface
    {
        return app(ModelUpdaterFactoryInterface::class);
    }

}
