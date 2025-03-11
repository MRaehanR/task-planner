<?php

namespace App\Http\Requests\Authentication;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends Request
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
            'username' => 'required|min:5|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|max:15|unique:users'
        ];
    }
}
