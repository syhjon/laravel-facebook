<?php

namespace App\Http\Requests;

use App\Exceptions\DomainValidationException;
use Illuminate\Contracts\Validation\Validator;

abstract class ApiRequest extends ApplicationRequest
{
    abstract protected function validationExceptionCode(): int;

    protected function failedValidation(Validator $validator): never
    {
        throw new DomainValidationException(
            $validator->errors()->toArray(),
            $this->validationExceptionCode(),
        );
    }
}
