<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'version' => '2023-06-01',
        'base_url' => 'https://api.anthropic.com/v1/messages',
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5-20250929'),
        'max_tokens' => 1200,
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'max_tokens' => 800,
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434/v1/chat/completions'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
        'max_tokens' => 1200,
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'max_tokens' => 1200,
    ],

    'assistant' => [
        'provider' => env('ASSISTANT_PROVIDER', 'groq'),
    ],
];
