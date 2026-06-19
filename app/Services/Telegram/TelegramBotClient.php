<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotClient
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function send(string $method, array $payload): array
    {
        $token = (string) config('telegram.bot_token', '');

        if ($token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        $baseUrl = rtrim((string) config('telegram.api_base_url', 'https://api.telegram.org'), '/');

        return Http::asJson()
            ->post("{$baseUrl}/bot{$token}/{$method}", $payload)
            ->throw()
            ->json();
    }
}
