<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LogoutRequest;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class LogoutController extends Controller
{
    private $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(LogoutRequest $request)
    {
        $this->authService->logout($request);

        return Response::success('User Logout Successfully', HttpFoundationResponse::HTTP_OK);
    }
}
