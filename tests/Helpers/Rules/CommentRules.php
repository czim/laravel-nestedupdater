<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Rules;

class CommentRules
{

    public function rules(): array
    {
        return [
            'title' => 'string',
            'body'  => 'required',
        ];
    }

}
