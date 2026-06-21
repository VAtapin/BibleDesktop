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

        $inboundMessage = TelegramMessage::query()
            ->where('telegram_id', $message->telegram_id)
            ->where('direction', 'inbound')
            ->where('status', 'new')
            ->latest('id')
            ->first();

        TelegramMessage::query()
            ->where('telegram_id', $message->telegram_id)
            ->where('direction', 'inbound')
            ->where('status', 'new')
            ->update([
                'status' => 'answered',
                'admin_reply' => $reply,
                'answered_at' => now(),
            ]);

        if ($message->direction === 'inbound' && $message->status !== 'new') {
            $message->update([
            'status' => 'answered',
            'admin_reply' => $reply,
            'answered_at' => now(),
            ]);
        }

        TelegramMessage::query()->create([
            'user_id' => $message->user_id,
            'telegram_id' => $message->telegram_id,
            'telegram_username' => $message->telegram_username,
            'chat_id' => $message->chat_id,
            'direction' => 'outbound',
            'status' => 'sent',
            'body' => $reply,
            'answered_at' => now(),
            'metadata_json' => [
                'reply_to_message_id' => $inboundMessage?->id ?? $message->id,
            ],
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
