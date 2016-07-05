<?php
namespace Czim\NestedModelUpdater\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TemporaryId
 * 
 * Container for information about a single temporary ID's status.
 */
class TemporaryId
{

    /**
     * Wether the model has been created for this temporary ID
     *
     * @var boolean
     */
    protected $created = false;

    /**
     * The data to use to create the model
     *
     * @var null|array
     */
    protected $data;

    /**
     * The created model, if it is created
     *
     * @var null|Model
     */
    protected $model;

    /**
     * The model class FQN, if it is known
     *
     * @var null|string
     */
    protected $modelClass;


    /**
     * @param boolean $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCreated()
    {
        return $this->created;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Model|null $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        if ($model->exists) {
            $this->created = true;
        }

        return $this;
    }

    /**
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setModelClass($class)
    {
        $this->modelClass = $class;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }
    
}
