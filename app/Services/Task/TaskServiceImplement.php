<?php

namespace App\Services\Task;

use App\DataTransferObjects\Task\TaskDTO;
use App\Exceptions\ResponseException;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TaskServiceImplement implements TaskService
{
    public function getTasks(array $params = [])
    {
        $userId = Auth::user()->id;
        $query = Task::where('user_id', $userId);

        if (isset($params['day'])) {
            $query->where('day_of_week', $params['day']);
        }

        if (isset($params['is_reccurring'])) {
            $query->where('is_reccurring', $params['is_reccurring']);
        }

        $tasks = $query->get();

        return $tasks->map(function ($task) {
            return new TaskDTO(
                id: $task->id,
                title: $task->title,
                desc: $task->desc,
                day_of_week: $task->day_of_week,
                start_time: $task->start_time,
                end_time: $task->end_time,
                all_day: $task->all_day,
                is_reccurring: $task->is_reccurring,
                is_fixed: $task->is_fixed,
                deadline: $task->deadline,
                start_time_attributes: TaskDTO::parseDateTime($task->start_time),
                end_time_attributes: TaskDTO::parseDateTime($task->end_time),
                deadline_attributes: TaskDTO::parseDateTime($task->deadline)
            );
        });
    }

    public function getTaskById(int $id)
    {
        $userId = Auth::user()->id;
        $task = Task::where('user_id', $userId)->find($id);

        if (!$task) {
            throw new ResponseException('Task Not Found', Response::HTTP_NOT_FOUND);
        }

        return new TaskDTO(
            id: $task->id,
            title: $task->title,
            desc: $task->desc,
            day_of_week: $task->day_of_week,
            start_time: $task->start_time,
            end_time: $task->end_time,
            all_day: $task->all_day,
            is_reccurring: $task->is_reccurring,
            is_fixed: $task->is_fixed,
            deadline: $task->deadline,
            start_time_attributes: TaskDTO::parseDateTime($task->start_time),
            end_time_attributes: TaskDTO::parseDateTime($task->end_time),
            deadline_attributes: TaskDTO::parseDateTime($task->deadline)
        );
    }

    public function deleteTaskById(int $id)
    {
        $userId = Auth::user()->id;
        $task = Task::where('user_id', $userId)->find($id);

        if (!$task) {
            throw new ResponseException('Task Not Found', Response::HTTP_NOT_FOUND);
        }

        $task->delete();
    }
}
