<?php

namespace App\Services\Task;

use App\DataTransferObjects\Task\TaskDTO;
use App\Exceptions\ResponseException;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use OpenAI\Client as OpenAIClient;

class TaskServiceImplement implements TaskService
{
    protected $openAIClient;

    public function __construct(OpenAIClient $openAIClient)
    {
        $this->openAIClient = $openAIClient;
    }

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
                start_time_attributes: TaskDTO::parseDateTime($start_time),
                end_time_attributes: TaskDTO::parseDateTime($end_time),
                deadline_attributes: $task->deadline ? TaskDTO::parseDateTime($task->deadline) : null
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
            start_time_attributes: TaskDTO::parseDateTime($start_time),
            end_time_attributes: TaskDTO::parseDateTime($end_time),
            deadline_attributes: $task->deadline ? TaskDTO::parseDateTime($task->deadline) : null
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

        $existingTasks = Task::where('user_id', $userId)->get();

        $tasksData = $existingTasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'desc' => $task->desc,
                'day_of_week' => $task->day_of_week,
                'start_time' => $task->start_time,
                'end_time' => $task->end_time,
                'all_day' => $task->all_day,
                'is_recurring' => $task->is_recurring,
                'is_fixed' => $task->is_fixed,
                'deadline' => $task->deadline,
            ];
        })->toArray();

        $tasksData[] = [
            'id' => null,
            'title' => $params['title'],
            'desc' => $params['desc'],
            'day_of_week' => $params['day_of_week'],
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'],
            'all_day' => $params['all_day'],
            'is_recurring' => $params['is_recurring'],
            'is_fixed' => $params['is_fixed'],
            'deadline' => $params['deadline'] ?? null,
        ];

        $functionDefinition = [
            'name' => 'schedule_tasks',
            'description' => 'Schedule tasks efficiently while maintaining the same structure.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'tasks' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => ['integer', 'null']],
                                'title' => ['type' => 'string'],
                                'desc' => ['type' => 'string'],
                                'day_of_week' => ['type' => 'string'],
                                'start_time' => ['type' => 'string'],
                                'end_time' => ['type' => 'string'],
                                'all_day' => ['type' => 'boolean'],
                                'is_recurring' => ['type' => 'boolean'],
                                'is_fixed' => ['type' => 'boolean'],
                                'deadline' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->openAIClient->chat()->create([
                'model' => 'gpt-4o-mini-2024-07-18',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI that schedules tasks in order to be more efficient and returns valid JSON.'],
                    ['role' => 'system', 'content' => 'You can only modify the date, the day_of_week, and the time not the title, desc, all_day, is_recurring, is_fixed, and deadline value.'],
                    ['role' => 'system', 'content' => 'You can modify the date, the day_of_week, and the time of each task that has false in is_fixed but You cannot modify tasks that have true in is_fixed. For example, if there is a clash of schedules or there are two task that accour at the same time, you can change the time, day, or date task that is false in is_fixed to make no more clashes schedules.'],
                    ['role' => 'system', 'content' => 'You can see there is a deadline, arrange the schedule to make it easier to finish the task before the deadline.'],
                    ['role' => 'user', 'content' => json_encode(['tasks' => $tasksData])],
                ],
                'functions' => [$functionDefinition],
                'function_call' => 'auto',
            ]);

            $scheduledTasks = json_decode($response->choices[0]->message->functionCall->arguments, true)['tasks'];

            $updatedTasks = [];
            foreach ($scheduledTasks as $scheduledTask) {
                if ($scheduledTask['id']) {
                    Task::where('id', $scheduledTask['id'])->update([
                        'day_of_week' => $scheduledTask['day_of_week'],
                        'start_time' => $scheduledTask['start_time'],
                        'end_time' => $scheduledTask['end_time'],
                    ]);
                    $updatedTasks[] = Task::find($scheduledTask['id']);
                } else {
                    $task = Task::create([
                        'user_id' => $userId,
                        'title' => $scheduledTask['title'],
                        'desc' => $scheduledTask['desc'],
                        'day_of_week' => $scheduledTask['day_of_week'],
                        'start_time' => $scheduledTask['start_time'],
                        'end_time' => $scheduledTask['end_time'],
                        'all_day' => $scheduledTask['all_day'],
                        'is_recurring' => $scheduledTask['is_recurring'],
                        'is_fixed' => $scheduledTask['is_fixed'],
                        'deadline' => $scheduledTask['deadline'] ?? null,
                    ]);
                    $updatedTasks[] = $task;
                }
            }

            return collect($updatedTasks)->map(function ($task) {
                return new TaskDTO(
                    id: $task->id,
                    title: $task->title,
                    desc: $task->desc,
                    day_of_week: $task->day_of_week,
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
            });
        } catch (\Exception $e) {
            throw new ResponseException('Invalid response structure: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
