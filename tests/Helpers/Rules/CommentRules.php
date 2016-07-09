<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class CommentRules
{

    public function rules()
    {
        return [
            'title' => 'string',
            'body'  => 'required',
        ];
    }

}
