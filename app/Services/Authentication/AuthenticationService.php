<?php

namespace App\Services\Authentication;

use App\DataTransferObjects\Authentication\LoginDTO;
use App\DataTransferObjects\Authentication\RegisterDTO;
use App\Http\Requests\Authentication\LogoutRequest;

interface AuthenticationService
{
    public function login(LoginDTO $data);
    public function register(RegisterDTO $data);
    public function logout(LogoutRequest $request);
}
