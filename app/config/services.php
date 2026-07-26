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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'app_secret' => env('META_APP_SECRET'),
    ],

    'llm' => [
        // OpenAI: https://api.openai.com/v1 + OPENAI_API_KEY
        // Groq:   https://api.groq.com/openai/v1 + GROQ_API_KEY
        'api_key' => env('OPENAI_API_KEY') ?: env('GROQ_API_KEY'),
        'base_url' => env('LLM_BASE_URL') ?: (
            env('OPENAI_API_KEY') ? 'https://api.openai.com/v1' : (
                env('GROQ_API_KEY') ? 'https://api.groq.com/openai/v1' : ''
            )
        ),
        'model' => env('LLM_MODEL', env('GROQ_API_KEY') && ! env('OPENAI_API_KEY') ? 'llama-3.3-70b-versatile' : 'gpt-4o-mini'),
        'stt_base_url' => env('STT_BASE_URL') ?: (
            env('OPENAI_API_KEY') ? 'https://api.openai.com/v1' : (
                env('GROQ_API_KEY') ? 'https://api.groq.com/openai/v1' : ''
            )
        ),
        'stt_model' => env('STT_MODEL', env('OPENAI_API_KEY') ? 'whisper-1' : 'whisper-large-v3'),
    ],

    'finova' => [
        'nlu_confidence_threshold' => (float) env('FINOVA_NLU_CONFIDENCE_THRESHOLD', 0.75),
    ],

    'pluggy' => [
        'client_id' => env('PLUGGY_CLIENT_ID'),
        'client_secret' => env('PLUGGY_CLIENT_SECRET'),
        // Sandbox e produção usam o mesmo host; o ambiente é o da aplicação no dashboard.
        'base_url' => env('PLUGGY_BASE_URL', 'https://api.pluggy.ai'),
    ],

];
