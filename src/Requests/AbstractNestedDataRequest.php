<?php

declare(strict_types=1);

namespace Czim\NestedModelUpdater\Requests;

use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorInterface;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class AbstractNestedDataRequest extends FormRequest
{
    /**
     * Fully qualified namespace for the NestedValidatorInterface class to use.
     *
     * @var class-string<NestedValidatorInterface>
     */
    protected string $validatorClass = NestedValidatorInterface::class;


    /**
     * Returns FQN of model class to validate nested data for (at the top level).
     *
     * @return class-string<TModel>
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

        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        if (! $validator->validate($this->all(), $this->isCreating())) {
            $this->failedNestedValidation($validator->messages());
        }
    }

    protected function failedNestedValidation(MessageBag $errors): never
    {
        throw new HttpResponseException(
            $this->response(
                $errors->toArray()
            )
        );
    }

    protected function makeNestedValidator(): NestedValidatorInterface
    {
        return $this->getNestedValidatorFactory()
            ->make($this->getNestedValidatorClass(), [$this->getNestedModelClass()]);
    }

    /**
     * Returns FQN for the nested validator class to validate the nested data.
     *
     * @return class-string<NestedValidatorInterface>
     */
    protected function getNestedValidatorClass(): string
    {
        return $this->validatorClass;
    }

    protected function getNestedValidatorFactory(): NestedValidatorFactoryInterface
    {
        return app(NestedValidatorFactoryInterface::class);
    }
}
