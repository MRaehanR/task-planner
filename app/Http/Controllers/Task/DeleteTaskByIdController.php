<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class DeleteTaskByIdController extends Controller
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
        $result = $this->taskService->deleteTaskById($id);

        return Response::success('Delete Task By ID Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
