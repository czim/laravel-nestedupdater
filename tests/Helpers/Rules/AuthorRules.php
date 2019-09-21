<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class AuthorRules
{

    public function rules(): array
    {
        return [
            'name' => 'string',
        ];
    }

}
