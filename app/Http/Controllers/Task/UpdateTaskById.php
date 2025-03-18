<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\UpdateTaskByIdRequest;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class UpdateTaskById extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke($id, UpdateTaskByIdRequest $request)
    {
        $params = $request->all();
        $result = $this->taskService->updateTaskById($id, $params);

        return Response::success('Update By ID Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
