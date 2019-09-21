<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class GenreRules
{

    public function rules(): array
    {
        return [
            'id'   => 'integer',
            'name' => 'string|unique:genres,name',
        ];
    }

}
