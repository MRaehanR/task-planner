<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskByIdRequest extends Request
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
            'title' => 'sometimes|string|max:255',
            'desc' => 'sometimes|string|nullable',
            'day_of_week' => 'sometimes|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'sometimes|date_format:Y-m-d H:i:s|nullable',
            'end_time' => 'sometimes|date_format:Y-m-d H:i:s|nullable|after_or_equal:start_time',
            'all_day' => 'sometimes|boolean',
            'is_completed' => 'sometimes|boolean',
            'is_recurring' => 'sometimes|boolean',
            'is_fixed' => 'sometimes|boolean',
            'deadline' => 'sometimes|date_format:Y-m-d H:i:s|nullable|after_or_equal:start_time',
        ];
    }
}
