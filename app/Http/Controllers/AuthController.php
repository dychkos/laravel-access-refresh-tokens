<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $service
    )
    {
    }

    /**
     * Login.
     *
     * Will return cookies with `{refreshToken: string}`.
     * @response array{data: array{user: UserResource, accessToken: string}, status: bool}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        if (!Auth::attempt($credentials)) {
            return $this->errorResponse(message: 'Wrong credentials.');
        }

        $user = Auth::user();
        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens($tokens, [
            'user' => UserResource::make($user)
        ]);
    }

    /**
     * Register.
     *
     * Will return cookies with `{refreshToken: string}`.
     * @response array{data: array{user: UserResource, accessToken: string}, status: bool}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = $request->validated();

        try {
            $user = $this->service->doRegistration($userData);
        } catch (\Exception $exception) {
            return $this->errorResponse(message: $exception->getMessage());
        }

        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens($tokens, [
            'user' => UserResource::make($user)
        ]);
    }

    /**
     * Refresh access token.
     *
     * Accept `{refreshToken: string}` from cookies.
     * @response array{data: array{accessToken: string}, status: bool}
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = Auth::user();
        $request->user()->tokens()->delete();
        $tokens = $this->service->generateTokens($user);

        return $this->sendResponseWithTokens($tokens);
    }

    /**
     * Logout.
     *
     * @response array{message: string, status: bool}
     */
    public function logout(Request $request): JsonResponse
    {
        if (Auth::check()) {
            $request->user()->tokens()->delete();
        }
        $cookie = cookie()->forget('refreshToken');

        return $this
            ->successResponse(message: 'Successfully logged out.')
            ->withCookie($cookie);
    }

    /**
     * Forgot password.
     *
     * Will send a link for restoring password by email.
     *
     * @response array{message: string, status: bool}
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $dto = $request->validated();

        $status = $this->service->sendResetPasswordLink($dto['email']);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(message: __($status));
        }

        return $this->errorResponse(message: __($status));
    }

    /**
     * Reset password.
     *
     * @response array{message: string, status: bool}
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->service->doPasswordReset($request->validated());

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(message: __($status));
        }

        return $this->errorResponse(message: __($status));
    }

    private function sendResponseWithTokens(array $tokens, $body = []): JsonResponse
    {
        $rtExpireTime = config('sanctum.rt_expiration');
        $cookie = cookie('refreshToken', $tokens['refreshToken'], $rtExpireTime, secure: true);

        return $this->successResponse(array_merge($body, [
            'accessToken' => $tokens['accessToken']
        ]))->withCookie($cookie);
    }
}
