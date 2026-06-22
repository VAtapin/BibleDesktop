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
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'bibleDesktop_bot'),
    'start_image_url' => env('TELEGRAM_START_IMAGE_URL', env('APP_URL', 'https://bible-desktop.com').'/images/telegram-start.png'),
    'api_base_url' => env('TELEGRAM_API_BASE_URL', 'https://api.telegram.org'),
    'send_responses' => env('TELEGRAM_SEND_RESPONSES', false),
];
