<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Test\Helpers\Requests;

use Czim\NestedModelUpdater\Requests\AbstractNestedDataRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends AbstractNestedDataRequest<TModel>
 */
abstract class AbstractNestedTestRequest extends AbstractNestedDataRequest
{
    /**
     * Override to prevent redirect response for easier testing.
     *
     * @param array<int|string, mixed> $errors
     * @return Response
     */
    public function response(array $errors): Response
    {
        return response($errors, 422);
    }
}
