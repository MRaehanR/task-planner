<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\RearrangeTaskByAIRequest;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class RearrangeTaskByAI extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(RearrangeTaskByAIRequest $request)
    {
        $params = $request->all();
        $result = $this->taskService->rearrangeByAI($params);

        return Response::success('Rearrange By AI Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
