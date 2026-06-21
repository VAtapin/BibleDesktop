<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'default_translation' => env('TELEGRAM_DEFAULT_TRANSLATION', 'BQ_RUSSIAN_RST'),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'web_app_url' => env('TELEGRAM_WEB_APP_URL'),
    'send_responses' => env('TELEGRAM_SEND_RESPONSES', false),
];
