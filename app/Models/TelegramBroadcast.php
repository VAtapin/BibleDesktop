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

class TelegramBroadcast extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'body',
        'status',
        'sent_count',
        'sent_at',
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }
}
