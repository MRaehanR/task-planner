<?php

namespace App\Http\Controllers\Authentication;

use App\DataTransferObjects\Authentication\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class LoginController extends Controller
{
    private $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        $request = LoginDTO::fromRequest($request->all());
        $result = $this->authService->login($request);

        return Response::success('User Login Successfully', HttpFoundationResponse::HTTP_OK, $result);
    }
}
