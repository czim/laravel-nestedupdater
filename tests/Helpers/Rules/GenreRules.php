<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class GenreRules
{

    public function rules()
    {
        return [
            'id'   => 'integer',
            'name' => 'string|unique:genres,name',
        ];
    }

}
