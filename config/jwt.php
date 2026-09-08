<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key used to sign JWT tokens. This should be set via the
    | JWT_SECRET environment variable and generated using:
    | php artisan jwt:secret
    |
    */

    'secret' => env('JWT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign JWT tokens.
    | Supported: HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512
    |
    */

    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | JWT TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | How long the JWT token is valid for (in minutes).
    | Default: 15 minutes
    |
    */

    'ttl' => env('JWT_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh TTL
    |--------------------------------------------------------------------------
    |
    | How long the refresh token is valid for (in minutes).
    | Default: 10080 minutes (7 days)
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 10080),

    /*
    |--------------------------------------------------------------------------
    | JWT Claims
    |--------------------------------------------------------------------------
    |
    | Claims to add to every JWT token
    |
    */

    'claims' => [
        'iss' => env('APP_URL'),
        'aud' => env('APP_URL'),
    ],

];
