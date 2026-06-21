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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_id',
        'telegram_username',
        'chat_id',
        'direction',
        'status',
        'body',
        'admin_reply',
        'answered_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }
}
