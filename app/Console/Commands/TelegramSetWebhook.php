<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotClient;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook
        {url : Public HTTPS webhook URL}
        {--drop-pending : Drop pending Telegram updates}';

    protected $description = 'Register the Telegram bot webhook URL.';

    public function handle(TelegramBotClient $client): int
    {
        $payload = [
            'url' => (string) $this->argument('url'),
            'drop_pending_updates' => (bool) $this->option('drop-pending'),
        ];

        $secret = config('telegram.webhook_secret');

        if ($secret) {
            $payload['secret_token'] = $secret;
        }

        $response = $client->send('setWebhook', $payload);

        if (($response['ok'] ?? false) !== true) {
            $this->error((string) ($response['description'] ?? 'Telegram rejected webhook registration.'));

            return self::FAILURE;
        }

        $this->components->info((string) ($response['description'] ?? 'Telegram webhook registered.'));

        return self::SUCCESS;
    }
}
