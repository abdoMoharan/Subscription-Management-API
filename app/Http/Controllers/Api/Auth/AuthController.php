<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    const TOKEN_NAME = 'token';
    public function login(AuthRequest $request)
    {
        $data = $request->getData();
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_UNAUTHORIZED, 'Email not registered');
        }
        if (! Hash::check($data['password'], $user->password)) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_UNAUTHORIZED, 'Invalid password');
        }
        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;
        $data          = $user->only(['name',  'phone']);
        $data['token'] = $token;
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'User logged in successfully', $data);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
