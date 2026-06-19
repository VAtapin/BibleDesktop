<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
