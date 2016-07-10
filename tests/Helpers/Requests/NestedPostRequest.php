<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Requests;

use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

class NestedPostRequest extends AbstractNestedTestRequest
{

    public function authorize()
    {
        return true;
    }

    protected function getNestedModelClass()
    {
        return Post::class;
    }

    protected function isCreating()
    {
        // As an example, the difference between creating and updating here is
        // simulated as that of the difference between using a POST and PUT method.

        return request()->getMethod() != 'PUT' && request()->getMethod() != 'PATCH';
    }

}
