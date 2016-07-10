<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Requests;

use Czim\NestedModelUpdater\Requests\AbstractNestedDataRequest;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractNestedTestRequest extends AbstractNestedDataRequest
{

    /**
     * Override to prevent redirect response for easier testing
     *
     * @param array $errors
     * @return Response
     */
    public function response(array $errors)
    {
        return response($errors, 422);
    }

}
