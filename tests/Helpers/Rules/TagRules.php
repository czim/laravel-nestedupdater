<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class TagRules
{

    public function rules(string $type = 'create'): array
    {
        if ($type !== 'create') {
            return [
                // added deliberately weird rules to test merging of inherent + custom model rules
                'id'   => 'integer|min:2|exists:genres,id',
                'name' => 'string|max:30|unique:tags',
            ];
        }

        return [
            'name' => 'string|max:30|unique:tags',
        ];
    }

}
