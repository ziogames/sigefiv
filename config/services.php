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
    | conventional file name for this type of information.
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
    */

    'giphy' => [
        'key' => env('GIPHY_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | n8n - ZOE
    |--------------------------------------------------------------------------
    */

    'n8n' => [

        // ZOE IA / SIGEFIV
        'zoe_webhook_url' => env(
            'N8N_ZOE_WEBHOOK_URL',
            'http://host.docker.internal:5678/webhook/zoe-sigefiv-v2'
        ),

        // ZOE Chat Vecinal
        'zoe_webhook' => env(
            'N8N_ZOE_CHAT_WEBHOOK_URL',
            'http://host.docker.internal:5678/webhook/zoe-chat-vecinal'
        ),
         'webhook_sinonimos' => env('N8N_WEBHOOK_SINONIMOS_URL'),
    'webhook_descripcion' => env('N8N_WEBHOOK_DESCRIPCION_URL'),

    ],

    'zoe_voz_webhook' => env(
        'ZOE_VOZ_WEBHOOK_URL'
    ),

    'zoe_ai' => [
        'key' => env('ZOE_AI_KEY'),
    ],

];
