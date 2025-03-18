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

        return $tasks->map(function ($task) use ($params) {
            $start_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->start_time, $task->day_of_week, $params['current_date'] ?? null) : $task->start_time;
            $end_time = $task->is_recurring ? $this->adjustToCurrentWeek($task->end_time, $task->day_of_week, $params['current_date'] ?? null) : $task->end_time;

            return new TaskDTO(
                id: $task->id,
                title: $task->title,
                desc: $task->desc,
                day_of_week: $task->day_of_week,
                start_time: $start_time,
                end_time: $end_time,
                all_day: $task->all_day,
                is_completed: $task->is_completed,
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
            is_completed: $task->is_completed,
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

        try {
            $task = Task::create([
                'user_id' => $userId,
                'title' => $params['title'],
                'desc' => isset($params['desc']) ? $params['desc'] : null,
                'day_of_week' => $params['day_of_week'],
                'start_time' => $params['start_time'],
                'end_time' => isset($params['end_time']) ? $params['end_time'] : null,
                'all_day' => $params['all_day'],
                'is_completed' => isset($params['is_recurring']) ? $params['is_recurring'] : null,
                'is_recurring' => $params['is_recurring'],
                'is_fixed' => $params['is_fixed'],
                'deadline' => isset($params['deadline']) ? $params['deadline'] : null,
            ]);

            return new TaskDTO(
                id: $task->id,
                title: $task->title,
                desc: $task->desc,
                day_of_week: $task->day_of_week,
                start_time: $task->start_time,
                end_time: $task->end_time,
                all_day: $task->all_day,
                is_completed: $task->is_completed,
                is_recurring: $task->is_recurring,
                is_fixed: $task->is_fixed,
                deadline: $task->deadline,
                start_time_attributes: TaskDTO::parseDateTime($task->start_time),
                end_time_attributes: TaskDTO::parseDateTime($task->end_time),
                deadline_attributes: $task->deadline ? TaskDTO::parseDateTime($task->deadline) : null
            );
        } catch (\Exception $e) {
            throw new ResponseException('Invalid response structure: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function adjustToCurrentWeek($time, $dayOfWeek, $currentDate = null)
    {
        $currentDate = Carbon::parse($currentDate) ?? Carbon::now();
        $currentWeekDay = $currentDate->dayOfWeek;
        $targetDay = Carbon::parse($dayOfWeek)->dayOfWeek;
        $daysDifference = $targetDay - $currentWeekDay;

        $adjustedDate = $currentDate->copy()->addDays($daysDifference);
        $adjustedTime = Carbon::parse($time)->setDate($adjustedDate->year, $adjustedDate->month, $adjustedDate->day);

        return $adjustedTime->toDateTimeString();
    }

    private function adjustToCurrentMonth($time, $dayOfWeek, $currentDate)
    {
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
        $targetDay = Carbon::parse($dayOfWeek)->dayOfWeek;

        // Find the first occurrence of the target day in the current month
        $adjustedDate = Carbon::create($currentYear, $currentMonth, 1)->next($targetDay);

        // If the adjusted date is before the current date, move to the next occurrence
        if ($adjustedDate->lessThan($currentDate)) {
            $adjustedDate->addWeek();
        }

        $adjustedTime = Carbon::parse($time)->setDate($adjustedDate->year, $adjustedDate->month, $adjustedDate->day);

        return $adjustedTime->toDateTimeString();
    }

    public function rearrangeByAI(array $params)
    {
        $userId = Auth::user()->id;
        $range = $params['range'];
        $currentDate = Carbon::parse($params['current_date']);
        $currentDateForFilter = Carbon::parse($params['current_date']);
        $now = Carbon::now();

        // Query for regular tasks
        $regularTasksQuery = Task::where('user_id', $userId)
            ->where('is_recurring', false);

        // dd($currentDateForFilter->endOfWeek());

        // Filter regular tasks based on the range
        if ($range === 'day') {
            $regularTasksQuery->whereDate('start_time', $currentDateForFilter->toDateString());
        } else if ($range === 'week') {
            $regularTasksQuery->whereDate('start_time', '>=', $currentDateForFilter->startOfWeek())->whereDate('start_time', '<=', $currentDateForFilter->endOfWeek());;
        }

        // Get regular tasks
        $regularTasks = $regularTasksQuery->get();

        // Query for recurring tasks
        $recurringTasksQuery = Task::where('user_id', $userId)
            ->where('is_recurring', true);

        // Get recurring tasks
        $recurringTasks = $recurringTasksQuery->get();

        // Filter tasks based on is_recurring and is_fixed
        $nonRecurringNonFixedTasks = $regularTasks->filter(function ($task) use ($now) {
            return !$task->is_fixed && $task->start_time >= $now;
        });

        $recurringNonFixedTasks = $recurringTasks->filter(function ($task) {
            return !$task->is_fixed;
        });

        $recurringFixedTasks = $recurringTasks->filter(function ($task) {
            return $task->is_fixed;
        });

        // Adjust recurring fixed tasks to the current week if necessary
        $adjustedRecurringFixedTasks = $recurringFixedTasks->map(function ($task) use ($currentDate) {
            // dd(Carbon::parse($task->start_time)->toDateString(), $currentDate->toDateString());
            if (Carbon::parse($task->start_time)->toDateString() !== $currentDate->toDateString() || Carbon::parse($task->end_time)->toDateString() !== $currentDate->toDateString()) {
                $task->start_time = $this->adjustToCurrentWeek($task->start_time, $task->day_of_week, $currentDate);
                $task->end_time = $this->adjustToCurrentWeek($task->end_time, $task->day_of_week, $currentDate);
            }
            return $task;
        });

        // Combine all tasks
        $allTasks = $nonRecurringNonFixedTasks->merge($recurringNonFixedTasks)->merge($adjustedRecurringFixedTasks);

        // dd($allTasks);

        // Prepare data for AI
        $tasksData = $allTasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'desc' => $task->desc,
                'day_of_week' => $task->day_of_week,
                'start_time' => $task->start_time,
                'end_time' => $task->end_time,
                'all_day' => $task->all_day,
                'is_completed' => $task->is_completed,
                'is_recurring' => $task->is_recurring,
                'is_fixed' => $task->is_fixed,
                'deadline' => $task->deadline,
            ];
        })->toArray();

        // Define function for AI
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
                                'is_completed' => ['type' => 'boolean'],
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
                'model' => 'gpt-4o-2024-08-06',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI scheduling assistant that creates an optimized, conflict-free, and well-balanced schedule for the user. Your goal is to avoid task overload, prevent overlapping, and ensure a smooth workflow.'],
                    ['role' => 'system', 'content' => 'You **must not modify** any task where `is_fixed = true`.'],
                    ['role' => 'system', 'content' => 'For tasks with `is_fixed = false`, you have the flexibility to adjust their timing to resolve conflicts and improve the overall schedule.'],
                    ['role' => 'system', 'content' => 'If a non-fixed task conflicts with a fixed task, **you must reschedule the non-fixed task** to remove the conflict.'],
                    ['role' => 'system', 'content' => 'If two non-fixed tasks conflict, **you should adjust one or both of them** to resolve the overlap.'],
                    ['role' => 'system', 'content' => 'You can modify `start_time`, `end_time`, and `day_of_week` of non-fixed tasks as needed.'],
                    ['role' => 'user', 'content' => json_encode(['tasks' => $tasksData])],
                ],
                'functions' => [$functionDefinition],
                'function_call' => 'auto',
            ]);

            $scheduledTasks = json_decode($response->choices[0]->message->functionCall->arguments, true)['tasks'];

            // Validate and revert changes to fixed tasks
            foreach ($tasksData as $originalTask) {
                if ($originalTask['is_fixed']) {
                    foreach ($scheduledTasks as &$scheduledTask) {
                        if ($scheduledTask['id'] == $originalTask['id']) {
                            if ($scheduledTask['start_time'] != $originalTask['start_time'] || $scheduledTask['end_time'] != $originalTask['end_time'] || $scheduledTask['day_of_week'] != $originalTask['day_of_week']) {
                                // Revert changes to fixed tasks
                                $scheduledTask['start_time'] = $originalTask['start_time'];
                                $scheduledTask['end_time'] = $originalTask['end_time'];
                                $scheduledTask['day_of_week'] = $originalTask['day_of_week'];
                            }
                        }
                    }
                }
            }

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
                        'is_completed' => $scheduledTask['is_completed'],
                        'is_recurring' => $scheduledTask['is_recurring'],
                        'is_fixed' => $scheduledTask['is_fixed'],
                        'deadline' => $scheduledTask['deadline'] ?? null,
                    ]);
                    $updatedTasks[] = $task;
                }
            }

            $paramsForGetTasks = [
                "current_date" => $currentDate,
            ];
            $getAllTasks = $this->getTasks($paramsForGetTasks);

            return collect($getAllTasks)->map(function ($task) {
                return new TaskDTO(
                    id: $task->id,
                    title: $task->title,
                    desc: $task->desc,
                    day_of_week: $task->day_of_week,
                    start_time: $task->start_time,
                    end_time: $task->end_time,
                    all_day: $task->all_day,
                    is_completed: $task->is_completed,
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
}
