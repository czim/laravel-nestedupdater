<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Traits;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterFactoryInterface;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin Model
 */
trait NestedUpdatable
{
    /**
     * @param array<string, mixed> $attributes
     * @return Model|null
     */
    public static function create(array $attributes = [])
    {
        /** @var NestedUpdatable&Model $this */
        $model = new static;

        /** @var ModelUpdaterInterface<TModel, Model> $updater */
        $updater = $model->getModelUpdaterInstance();

        $result = $updater->create($attributes);

        return $result->model();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        /** @var NestedUpdatable&Model $this */
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
     * @return ModelUpdaterInterface<TModel, Model>
     */
    protected function getModelUpdaterInstance(): ModelUpdaterInterface
    {
        $class = (property_exists($this, 'modelUpdaterClass'))
            ? $this->modelUpdaterClass
            : ModelUpdaterInterface::class;

        $config = (property_exists($this, 'modelUpdaterConfigClass'))
            ? app($this->modelUpdaterConfigClass)
            : null;

        return $this->getModelUpdaterFactory()->make($class, [ get_class($this), null, null, null, $config ]);
    }

    protected function getModelUpdaterFactory(): ModelUpdaterFactoryInterface
    {
        return app(ModelUpdaterFactoryInterface::class);
    }
}
