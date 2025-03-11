<?php

namespace App\Services\Task;

use App\DataTransferObjects\Task\TaskDTO;
use App\Exceptions\ResponseException;
use App\Models\Task;
use Carbon\Carbon;
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

        if (isset($params['is_recurring'])) {
            $query->where('is_recurring', $params['is_recurring']);
        }

        if (isset($params['is_fixed'])) {
            $query->where('is_fixed', $params['is_fixed']);
        }

        $tasks = $query->get();

        return $tasks->map(function ($task) {
            $start_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->start_time, $task->day_of_week) : $task->start_time;
            $end_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->end_time, $task->day_of_week) : $task->end_time;

            return new TaskDTO(
                id: $task->id,
                title: $task->title,
                desc: $task->desc,
                day_of_week: $task->day_of_week,
                start_time: $start_time,
                end_time: $end_time,
                all_day: $task->all_day,
                is_recurring: $task->is_recurring,
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


        $start_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->start_time, $task->day_of_week) : $task->start_time;
        $end_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->end_time, $task->day_of_week) : $task->end_time;

        return new TaskDTO(
            id: $task->id,
            title: $task->title,
            desc: $task->desc,
            day_of_week: $task->day_of_week,
            start_time: $start_time,
            end_time: $end_time,
            all_day: $task->all_day,
            is_recurring: $task->is_recurring,
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

    public function createTask(array $params)
    {
        $userId = Auth::user()->id;
        $task = Task::create([
            'user_id' => $userId,
            'title' => $params['title'],
            'desc' => $params['desc'],
            'day_of_week' => $params['day_of_week'],
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'],
            'all_day' => $params['all_day'],
            'is_recurring' => $params['is_recurring'],
            'is_fixed' => $params['is_fixed'],
            'deadline' => $params['deadline'] ?? null,
        ]);

        return new TaskDTO(
            id: $task->id,
            title: $task->title,
            desc: $task->desc,
            day_of_week: $task->day_of_week->value,
            start_time: $task->start_time,
            end_time: $task->end_time,
            all_day: $task->all_day,
            is_recurring: $task->is_recurring,
            is_fixed: $task->is_fixed,
            deadline: $task->deadline,
            start_time_attributes: TaskDTO::parseDateTime($task->start_time),
            end_time_attributes: TaskDTO::parseDateTime($task->end_time),
            deadline_attributes: $task->deadline ? TaskDTO::parseDateTime($task->deadline) : null
        );
    }

    private function adjustToCurrentWeek($time, $dayOfWeek)
    {
        $currentDate = Carbon::now();
        $currentWeekDay = $currentDate->dayOfWeek;
        $targetDay = Carbon::parse($dayOfWeek)->dayOfWeek;
        $daysDifference = $targetDay - $currentWeekDay;

        $adjustedDate = $currentDate->copy()->addDays($daysDifference);
        $adjustedTime = Carbon::parse($time)->setDate($adjustedDate->year, $adjustedDate->month, $adjustedDate->day);

        return $adjustedTime->toDateTimeString();
    }
}
