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
                'is_completed' => $task->is_completed,
                'is_recurring' => $task->is_recurring,
                'is_fixed' => $task->is_fixed,
                'deadline' => $task->deadline,
            ];
        })->toArray();

        $tasksData[] = [
            'id' => null,
            'title' => $params['title'],
            'desc' => $params['desc'] ?? null,
            'day_of_week' => $params['day_of_week'],
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'] ?? null,
            'all_day' => $params['all_day'],
            'is_completed' => $params['is_completed'] ?? null,
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

                    // **Aturan untuk tugas yang tidak berulang & tidak fixed**
                    ['role' => 'system', 'content' => 'For tasks where `is_recurring = false` and `is_fixed = false`:'],
                    ['role' => 'system', 'content' => '- You **may modify start_time, end_time, and day_of_week**, but with restrictions.'],
                    ['role' => 'system', 'content' => '- The new `start_time` **must not be earlier** than the original `start_time`.'],
                    ['role' => 'system', 'content' => '- The new `end_time` **must not exceed the deadline** of the task.'],
                    ['role' => 'system', 'content' => '- You may move the task to a **different day**, but only if it remains within the allowed deadline.'],

                    ['role' => 'system', 'content' => '💡 **Ensure tasks do not overlap under any circumstances.** All tasks must have a clear, separate time slot.'],
                    ['role' => 'system', 'content' => '💡 **Always insert adequate breaks between tasks** to allow for rest and transitions. Aim for at least **15-30 minutes** between tasks.'],
                    ['role' => 'system', 'content' => '💡 **Distribute tasks evenly across available days** instead of cramming multiple tasks into a single day.'],
                    ['role' => 'system', 'content' => '💡 **If a task has flexibility, position it at a time that makes the overall day feel less overwhelming.**'],

                    // **Pendekatan Penyelesaian Konflik**
                    ['role' => 'system', 'content' => '### Conflict resolution approach:'],
                    ['role' => 'system', 'content' => '1. **First, try adjusting the task’s time later in the available range on the same day while ensuring a comfortable gap between tasks.**'],
                    ['role' => 'system', 'content' => '2. **If necessary, extend the end time slightly, but never beyond the deadline.**'],
                    ['role' => 'system', 'content' => '3. **If no good slot is available on the same day, move it to another day before the deadline, ensuring workload is balanced.**'],
                    ['role' => 'system', 'content' => '4. **If no valid slot is found within the deadline, return an error message.**'],

                    ['role' => 'system', 'content' => '### Key goals:'],
                    ['role' => 'system', 'content' => '- **Tasks must never overlap.**'],
                    ['role' => 'system', 'content' => '- **Each task should have at least 15-30 minutes of break time before the next one.**'],
                    ['role' => 'system', 'content' => '- **Spread tasks evenly across multiple days to avoid overload.**'],
                    ['role' => 'system', 'content' => '- **Ensure the schedule feels practical, allowing for productivity without stress.**'],
                    ['role' => 'system', 'content' => '**Create a well-paced, human-friendly schedule that helps the user stay productive and focused without burnout.**'],

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
                        'is_completed' => $scheduledTask['is_completed'],
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
}
