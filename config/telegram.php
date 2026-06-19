<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'default_translation' => env('TELEGRAM_DEFAULT_TRANSLATION', 'L1_RST'),
];
