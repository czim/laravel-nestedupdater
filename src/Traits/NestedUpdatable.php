<?php
namespace Czim\NestedModelUpdater\Traits;

use App;
use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;

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
    protected function getModelUpdaterInstance()
    {
        $class = (property_exists($this, 'modelUpdaterClass'))
            ?   $this->modelUpdaterClass
            :   ModelUpdaterInterface::class;

        $config = (property_exists($this, 'modelUpdaterConfigClass'))
            ?   App::make($this->modelUpdaterConfigClass)
            :   null;

        return App::make($class, [ get_class($this), null, null, null, $config ]);
    }

}
