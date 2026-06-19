<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_webhook_returns_help_action_for_start_command(): void
    {
        config(['telegram.webhook_secret' => 'secret']);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/start',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('actions.0.method', 'sendMessage')
            ->assertJsonPath('actions.0.payload.chat_id', 123);
    }

    public function test_telegram_webhook_rejects_invalid_secret(): void
    {
        config(['telegram.webhook_secret' => 'secret']);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/help',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ])->assertForbidden();
    }

    public function test_telegram_webhook_can_send_actions_to_telegram_api(): void
    {
        config([
            'telegram.bot_token' => 'token',
            'telegram.webhook_secret' => 'secret',
            'telegram.send_responses' => true,
        ]);
        Http::fake([
            'https://api.telegram.org/bottoken/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123],
                'text' => '/help',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret',
        ])
            ->assertOk()
            ->assertJsonPath('sent', 1);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottoken/sendMessage'
            && $request['chat_id'] === 123);
    }
}
