<?php

namespace Auth;

use Laravel\Sanctum\PersonalAccessToken;
use Tests\Feature\Auth\AuthTestHelper;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function testCanLogout(): void
    {
        $user = AuthTestHelper::mockUser();

        $accessToken = AuthTestHelper::generateTokens($user)['accessToken'];

        $response = $this
            ->actingAs($user)
            ->withCredentials()
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson(route('logout'));

        $response->assertStatus(200);

        $response->assertCookie('refreshToken');

        $this->assertEquals(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());

        $response->assertJson([
            'status' => true,
            'message' => 'Successfully logged out.',
            'data' => []
        ]);

        AuthTestHelper::clearUser($user);
    }

    public function testCanLogoutWithExpiredSession(): void
    {
        $response = $this->postJson(route('logout'));

        $response->assertStatus(200);

        $response->assertCookie('refreshToken');

        $response->assertJson([
            'status' => true,
            'message' => 'Successfully logged out.',
            'data' => []
        ]);
    }
}
