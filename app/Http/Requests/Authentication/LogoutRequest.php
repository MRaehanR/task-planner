<?php

namespace App\Http\Requests\Authentication;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class LogoutRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    protected function prepareForValidation()
    {
        $authorizationHeader = $this->header('Authorization');

        if (!$authorizationHeader || !preg_match('/^Bearer\s+[\w-]+/', $authorizationHeader)) {
            throw new HttpResponseException(Response::error('Bearer token is required in the Authorization header.', HttpFoundationResponse::HTTP_BAD_REQUEST));
        }
    }
}
