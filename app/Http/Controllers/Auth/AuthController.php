<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = explode('|', $user->createToken('auth_token')->plainTextToken)[1];

        return ApiResponse::success(Response::HTTP_CREATED, 'User created successfully',
            [
                'user' => $user,
                'token' => $token,
            ]
        );
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return ApiResponse::error(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid email or password');
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $token = explode('|', $user->createToken('auth_token')->plainTextToken)[1];

        return ApiResponse::success(Response::HTTP_OK, 'Logged in successfully',
            [
                'user' => $user,
                'token' => $token,
            ]
        );
    }

    public function logout()
    {
        $user = Auth::user();
        $user->tokens()->delete();

        return ApiResponse::success(Response::HTTP_OK, 'Logout successful');
    }
}
