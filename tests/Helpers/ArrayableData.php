<?php
namespace Czim\NestedModelUpdater\Test\Helpers;

use Illuminate\Contracts\Support\Arrayable;

class ArrayableData implements Arrayable
{

    /**
     * @var array
     */
    protected $array;

    /**
     * @param array $array
     */
    public function __construct(array $array)
    {
        $this->array = $array;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->array;
    }
}
