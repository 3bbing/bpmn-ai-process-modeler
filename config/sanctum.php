<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')), 
    'expiration' => null,
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'add_cookies' => Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        'start_session' => Illuminate\Session\Middleware\StartSession::class,
    ],
];
