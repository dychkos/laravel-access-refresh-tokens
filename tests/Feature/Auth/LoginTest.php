<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{

    public function testCannotLoginWithoutRequiredFields(): void
    {
        $response = $this->postJson(route('login'));

        $response->assertStatus(422);

        $response->assertInvalid(['password', 'email']);

        $response->assertJsonFragment([
            'status' => false,
            'message' => 'Validation errors',
        ]);
    }

    public function testCannotLoginWithWrongPassword(): void
    {
        $user = AuthTestHelper::mockUser();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'status' => false,
            'message' => 'Wrong credentials.',
        ]);

        AuthTestHelper::clearUser($user);
    }

    public function testCannotLoginWithWrongEmail(): void
    {
        $response = $this->postJson(route('login'), [
            'email' => 'unexists@mail.example',
            'password' => 'incorrect',
        ]);

        $response->assertStatus(422);

        $response->assertJson([
            'status' => false,
            'message' => 'Wrong credentials.',
        ]);
    }

    public function testCanLogin(): void
    {
        $user = AuthTestHelper::mockUser();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);


        $response->assertStatus(200);

        $response->assertCookieNotExpired(
            'refreshToken'
        );

        $response->assertJsonStructure([
            'status',
            'message',
            'data' => AuthTestHelper::$loginSuccessBody
        ]);

        $accessToken = $response->decodeResponseJson()['data']['accessToken'];

        $this->assertTrue(AuthTestHelper::verifyAccessToken($accessToken));

        AuthTestHelper::clearUser($user);
    }

    public function testCanRefreshToken(): void
    {
        $user = AuthTestHelper::mockUser();

        $tokens = AuthTestHelper::generateTokens($user);

        $moveTime = config('sanctum.expiration') + 5;

        // Manually make access token expired
        $this->travel($moveTime)->minutes();
        $this->assertFalse(AuthTestHelper::verifyAccessToken($tokens['accessToken']));

        $response = $this
            ->withUnencryptedCookie('refreshToken', $tokens['refreshToken'])
            ->withCredentials()
            ->withHeader('Authorization', 'Bearer ' . $tokens['accessToken'])
            ->postJson(route('refresh'));


        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'accessToken'
            ]
        ]);

        $accessToken = $response->decodeResponseJson()['data']['accessToken'];

        $this->assertTrue(AuthTestHelper::verifyAccessToken($accessToken));

        AuthTestHelper::clearUser($user);
    }

    public function testCanRefreshTokenAfterLogin(): void
    {
        $user = AuthTestHelper::mockUser();

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $response->assertCookieNotExpired(
            'refreshToken'
        );

        $refreshToken = $response->getCookie('refreshToken', false)->getValue();

        $accessToken = $response->decodeResponseJson()['data']['accessToken'];

        // Manually make access token expired
        $this->travel(config('sanctum.expiration') + 5)->minutes();
        $this->assertFalse(AuthTestHelper::verifyAccessToken($accessToken));

        $response = $this
            ->withCookie('refreshToken', $refreshToken)
            ->withCredentials()
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson(route('refresh'));


        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'accessToken'
            ]
        ]);

        $accessToken = $response->decodeResponseJson()['data']['accessToken'];

        $this->assertTrue(AuthTestHelper::verifyAccessToken($accessToken));

        AuthTestHelper::clearUser($user);
    }

    public function testAccessTokenExpiration(): void
    {
        $user = AuthTestHelper::mockUser(true);

        $tokens = AuthTestHelper::generateTokens($user);

        // Manually make access token expired
        $this->travel(config('sanctum.expiration') + 10)->minutes();

        $response = $this->withHeader('Authorization', 'Bearer ' . $tokens['accessToken'])
            ->getJson(route('test'));


        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        AuthTestHelper::clearUser($user);
    }

    public function testRefreshTokenExpiration(): void
    {
        $user = AuthTestHelper::mockUser();

        $tokens = AuthTestHelper::generateTokens($user);

        // Manually make access token expired
        $this->travel(config('sanctum.rt_expiration') + 5)->minutes();

        $response = $this
            ->withCredentials()
            ->withUnencryptedCookie('refreshToken', $tokens['refreshToken'])
            ->withHeader('Authorization', 'Bearer ' . $tokens['accessToken'])
            ->postJson(route('refresh'));


        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        AuthTestHelper::clearUser($user);
    }
}
