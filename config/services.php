<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have a
    | conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env(
            'AWS_DEFAULT_REGION',
            'us-east-1'
        ),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),
            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter - Sigi
    |--------------------------------------------------------------------------
    |
    | La clave se mantiene en el archivo .env.
    | Nunca coloques la API key directamente aquí.
    |
    */

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),

        'model' => env(
            'OPENROUTER_MODEL',
            'mistralai/ministral-3b-2512'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Push - VAPID
    |--------------------------------------------------------------------------
    */

    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),

        'private_key' => env('VAPID_PRIVATE_KEY'),

        'subject' => env('VAPID_SUBJECT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth
    |--------------------------------------------------------------------------
    |
    | Credenciales utilizadas por Laravel Socialite para
    | iniciar sesión mediante una cuenta de Google.
    |
    */

    'google' => [

        'client_id' => env(
            'GOOGLE_CLIENT_ID'
        ),

        'client_secret' => env(
            'GOOGLE_CLIENT_SECRET'
        ),

        'redirect' => env(
            'GOOGLE_REDIRECT_URI',
            'http://localhost:8080/auth/google/callback'
        ),

        'android_client_id' => env(
            'GOOGLE_ANDROID_CLIENT_ID'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | SIGI - Text to Speech
    |--------------------------------------------------------------------------
    |
    | El motor TTS se configura mediante .env.
    | SIGEFIV no queda atado a un motor específico, permitiendo cambiar
    | entre Kokoro, Piper u otro servicio al migrar a Raspberry Pi 5.
    |
    */

    'sigi_tts' => [

        'url' => env('SIGI_TTS_URL'),

        'api_key' => env('SIGI_TTS_API_KEY'),

        'model' => env(
            'SIGI_TTS_MODEL',
            'kokoro'
        ),

        'voice' => env(
            'SIGI_TTS_VOICE',
            'es'
        ),

        'response_format' => env(
            'SIGI_TTS_RESPONSE_FORMAT',
            'mp3'
        ),

        'timeout' => env(
            'SIGI_TTS_TIMEOUT',
            60
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | GIPHY - Chat Vecinal
    |--------------------------------------------------------------------------
    |
    | La API key se mantiene en .env.
    | Nunca coloques la API key directamente aquí.
    |
    */

    'giphy' => [
        'key' => env('GIPHY_API_KEY'),
    ],
    
    'n8n' => [
    'zoe_webhook' => env('N8N_ZOE_WEBHOOK_URL'),
],

];