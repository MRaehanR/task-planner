<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\GetTasksRequest;
use App\Services\Task\TaskService;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class GetTasksController extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(GetTasksRequest $request)
    {
        $params = $request->all();
        $result = $this->taskService->getTasks($params);

        return Response::success('Get Tasks Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
