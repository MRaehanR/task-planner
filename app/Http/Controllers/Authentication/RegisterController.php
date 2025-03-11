<?php

namespace App\Http\Controllers\Authentication;

use App\DataTransferObjects\Authentication\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class RegisterController extends Controller
{
    private $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request)
    {
        $request = RegisterDTO::fromRequest($request->all());
        $result = $this->authService->register($request);

        return Response::success('User Register Successfully', HttpFoundationResponse::HTTP_CREATED, $result);
    }
}
