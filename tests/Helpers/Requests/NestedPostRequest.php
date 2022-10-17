<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Requests;

use Czim\NestedModelUpdater\Test\Helpers\Models\Post;

/**
 * @extends AbstractNestedTestRequest<Post>
 */
class NestedPostRequest extends AbstractNestedTestRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function getNestedModelClass(): string
    {
        return Post::class;
    }

    protected function isCreating(): bool
    {
        // As an example, the difference between creating and updating here is
        // simulated as that of the difference between using a POST and PUT method.
        return request()->getMethod() !== 'PUT'
            && request()->getMethod() !== 'PATCH';
    }
}
