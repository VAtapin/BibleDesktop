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

namespace Tests\Feature;

use App\Models\TelegramMessage;
use App\Services\Telegram\TelegramAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_adds_outbound_message_and_marks_latest_inbound_as_answered(): void
    {
        config(['telegram.bot_token' => 'token']);
        Http::fake([
            'https://api.telegram.org/bottoken/sendMessage' => Http::response(['ok' => true]),
        ]);

        $inbound = TelegramMessage::query()->create([
            'telegram_id' => '123',
            'telegram_username' => 'reader',
            'chat_id' => '123',
            'direction' => 'inbound',
            'status' => 'new',
            'body' => 'Вопрос',
        ]);
        $previousOutbound = TelegramMessage::query()->create([
            'telegram_id' => '123',
            'telegram_username' => 'reader',
            'chat_id' => '123',
            'direction' => 'outbound',
            'status' => 'sent',
            'body' => 'Старый ответ',
        ]);

        app(TelegramAdminService::class)->reply($previousOutbound, 'Новый ответ');

        $this->assertDatabaseHas('telegram_messages', [
            'id' => $inbound->id,
            'direction' => 'inbound',
            'status' => 'answered',
            'admin_reply' => 'Новый ответ',
        ]);
        $this->assertDatabaseHas('telegram_messages', [
            'id' => $previousOutbound->id,
            'direction' => 'outbound',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_id' => '123',
            'direction' => 'outbound',
            'status' => 'sent',
            'body' => 'Новый ответ',
        ]);
    }
}
