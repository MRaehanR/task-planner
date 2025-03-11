<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\GetTaskByIdRequest;
use App\Services\Task\TaskService;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class GetTaskByIdController extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke($id)
    {
        $result = $this->taskService->getTaskById($id);

        return Response::success('Get Task By ID Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
