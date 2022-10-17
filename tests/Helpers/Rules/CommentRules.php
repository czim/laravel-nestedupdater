<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class CommentRules
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'title' => 'string',
            'body'  => 'required',
        ];
    }
}
