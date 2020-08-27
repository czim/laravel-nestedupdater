<?php

namespace Czim\NestedModelUpdater\Requests;

use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class AbstractNestedDataRequest extends FormRequest
{
    /**
     * Fully qualified namespace for the NestedValidatorInterface class to use.
     *
     * @var string
     */
    protected $validatorClass = NestedValidatorInterface::class;


    /**
     * Returns FQN of model class to validate nested data for (at the top level).
     *
     * @return string
     */
    abstract protected function getNestedModelClass(): string;

    /**
     * Returns whether we are creating, as opposed to updating, the top
     * level model in the nested data tree.
     *
     * @return bool
     */
    abstract protected function isCreating(): bool;


    /**
     * Validate the class instance.
     */
    public function validateResolved(): void
    {
        $validator = $this->makeNestedValidator();


        if ( ! $this->passesAuthorization()) {

            $this->failedAuthorization();

        } elseif ( ! $validator->validate($this->all(), $this->isCreating())) {

            $this->failedNestedValidation($validator->messages());
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param MessageBag $errors
     * @return mixed
     */
    protected function failedNestedValidation(MessageBag $errors)
    {
        throw new HttpResponseException(
            $this->response(
                $errors->toArray()
            )
        );
    }

    /**
     * Returns the nested validator instance.
     *
     * @return NestedValidatorInterface
     */
    protected function makeNestedValidator(): NestedValidatorInterface
    {
        return $this->getNestedValidatorFactory()
            ->make($this->getNestedValidatorClass(), [ $this->getNestedModelClass() ]);
    }

    /**
     * Returns FQN for the nested validator class to validate the nested data.
     *
     * @return string
     */
    protected function getNestedValidatorClass(): string
    {
        return $this->validatorClass;
    }

    /**
     * @return NestedValidatorFactoryInterface
     */
    protected function getNestedValidatorFactory(): NestedValidatorFactoryInterface
    {
        return app(NestedValidatorFactoryInterface::class);
    }
}
