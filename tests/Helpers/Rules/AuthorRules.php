<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class AuthorRules
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
        ];
    }
}
