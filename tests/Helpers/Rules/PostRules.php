<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class PostRules
{
    /**
     * @return array<string, string>
     */
    public function rules(string $type = 'create'): array
    {
        if ($type !== 'create') {
            return [
                'title' => 'string|max:10',
                'body'  => 'required|string',
            ];
        }

        return [
            'title' => 'required|string|max:50',
            'body'  => 'string',
        ];
    }
}
