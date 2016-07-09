<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class PostRules
{

    public function rules($type = 'create')
    {
        if ($type !== 'create') {
            return [
                'title' => 'string|max:50',
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
