
# Laravel Sanctum | Access and Refresh tokens

Basic project with refresh token implementation.



## Configure

```php
> config/sanctum.php

return [
    // other code
    'expiration' => env('ACCESS_TOKEN_EXPIRATION_TIME', 5),
    'rt_expiration' => env('REFRESH_TOKEN_EXPIRATION_TIME', 24 * 60),
];


```

## Override Sanctum Configuration To Support Refresh Token

```php
> AppServiceProvider.php

  public function boot(): void
    {
        $this->overrideSanctumConfigurationToSupportRefreshToken();
    }

    private function overrideSanctumConfigurationToSupportRefreshToken(): void
    {
        Sanctum::$accessTokenAuthenticationCallback = function ($accessToken, $isValid) {
            $abilities = collect($accessToken->abilities);
            if (!empty($abilities) && $abilities[0] === TokenAbility::ISSUE_ACCESS_TOKEN->value) {
                return $accessToken->expires_at && $accessToken->expires_at->isFuture();
            }

            return $isValid;
        };

        Sanctum::$accessTokenRetrievalCallback = function ($request) {
            if (!$request->routeIs('refresh')) {
                return str_replace('Bearer ', '', $request->headers->get('Authorization'));
            }

            return $request->cookie('refreshToken') ?? '';
        };
    }
```


## Full explanation is available here:

https://medium.com/@dychkosergey/access-and-refresh-tokens-using-laravel-sanctum-037392e50509
