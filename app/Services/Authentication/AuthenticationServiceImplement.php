<?php

namespace App\Services\Authentication;

use App\DataTransferObjects\Authentication\LoginDTO;
use App\DataTransferObjects\Authentication\RegisterDTO;
use App\Exceptions\ResponseException;
use App\Http\Requests\Authentication\LogoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationServiceImplement implements AuthenticationService
{
    public function login(LoginDTO $data)
    {
        $user = User::where('username', $data->username)->first();

        if (!$user) {
            throw new ResponseException('Account not found', Response::HTTP_NOT_FOUND);
        }

        if ($user->username !== $data->username || !password_verify($data->password, $user->password)) {
            throw new ResponseException('Email or Password does not match', Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $user->createToken('access_token')->plainTextToken;

        return ['user' => $user, 'access_token' => $accessToken];
    }

    public function register(RegisterDTO $data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $data->username,
                'password' => $data->password,
                'phone' => $data->phone,
            ]);

            $accessToken = $user->createToken('access_token')->plainTextToken;

            DB::commit();

            return ['user' => $user, 'access_token' => $accessToken];
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function logout(LogoutRequest $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
