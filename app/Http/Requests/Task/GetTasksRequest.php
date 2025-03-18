<?php

namespace App\Http\Requests\Task;

use App\Enum\DayOfWeek;
use App\Http\Requests\Request;
use Illuminate\Validation\Rules\Enum;

class GetTasksRequest extends Request
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
            'day' => ['nullable', new Enum(DayOfWeek::class)],
            'is_recurring' => 'nullable|boolean',
            'is_fixed' => 'nullable|boolean',
            'is_completed' => 'nullable|boolean',
            'current_date' => 'nullable|date'
        ];
    }
}
