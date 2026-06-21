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

namespace App\Services\Telegram;

use App\Models\TelegramBroadcast;
use App\Models\TelegramMessage;
use Illuminate\Support\Facades\DB;

class TelegramAdminService
{
    public function __construct(private readonly TelegramBotClient $client) {}

    public function reply(TelegramMessage $message, string $reply): void
    {
        $this->client->send('sendMessage', [
            'chat_id' => $message->chat_id,
            'text' => $reply,
        ]);

        $message->update([
            'direction' => 'inbound',
            'status' => 'answered',
            'admin_reply' => $reply,
            'answered_at' => now(),
        ]);
    }

    public function sendBroadcast(TelegramBroadcast $broadcast): int
    {
        $users = DB::table('users')
            ->whereNotNull('telegram_id')
            ->get(['telegram_id']);
        $sent = 0;

        foreach ($users as $user) {
            $this->client->send('sendMessage', [
                'chat_id' => (string) $user->telegram_id,
                'text' => $broadcast->body,
            ]);
            $sent++;
        }

        $broadcast->update([
            'status' => 'sent',
            'sent_count' => $sent,
            'sent_at' => now(),
        ]);

        return $sent;
    }
}
