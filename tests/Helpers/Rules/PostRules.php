<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class PostRules
{

    public function rules(string $type = 'create'): array
    {
        if ($type !== 'create') {
            return [
                'title' => 'string|max:10',
                'body'  => 'required|string',
            ];
        } else {
            return [
                'title' => 'required|string|max:50',
                'body'  => 'string',
            ];
        }
    }

}
