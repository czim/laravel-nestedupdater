<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class GenreRules
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'id'   => 'integer',
            'name' => 'string|unique:genres,name',
        ];
    }
}
