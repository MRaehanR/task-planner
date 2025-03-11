<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class Request extends FormRequest
{
    /**
     * Validation Error Message
     * @return void
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(
            response()->json([
                'status' => false,
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'The given data was invalid.',
                'data' => [],
                'errors' => (new ValidationException($validator))->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        ));
    }
}
