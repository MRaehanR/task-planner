<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class CreateTaskController extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateTaskRequest $request)
    {
        $params = $request->all();
        $result = $this->taskService->createTask($params);

        return Response::success('Create Tasks Successfully', HttpFoundationResponse::HTTP_CREATED, $result);
    }
}
