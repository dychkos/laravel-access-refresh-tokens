<?php

namespace App\Services;

use App\Enums\TokenAbility;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    )
    {
    }

    /**
     * @param $userData
     *
     * @return User
     * @throws Exception
     */
    public function doRegistration($userData): User
    {
        if ($this->userRepository->checkEmailExists($userData['email'])) {
            throw new Exception('The email is already taken.');
        }

        return $this->userRepository->create($userData);
    }

    /**
     * @return array{
     *     accessToken: string,
     *     refreshToken: string,
     * }
     */
    public function generateTokens($user): array
    {
        $atExpireTime = now()->addMinutes(config('sanctum.expiration'));
        $rtExpireTime = now()->addMinutes(config('sanctum.rt_expiration'));

        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API], $atExpireTime);
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN], $rtExpireTime);

        return [
            'accessToken' => $accessToken->plainTextToken,
            'refreshToken' => $refreshToken->plainTextToken,
        ];
    }

    public function sendResetPasswordLink($email): string
    {
        return Password::sendResetLink([
            'email' => $email
        ]);
    }

    public function doPasswordReset($userData) {
        return Password::reset(
            $userData,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
                $user->save();
                event(new PasswordReset($user));
            }
        );
    }
}
