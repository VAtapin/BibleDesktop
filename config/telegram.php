<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'default_translation' => env('TELEGRAM_DEFAULT_TRANSLATION', 'BQ_RUSSIAN_RST'),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'send_responses' => env('TELEGRAM_SEND_RESPONSES', false),
];
